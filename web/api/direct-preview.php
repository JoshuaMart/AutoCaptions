<?php
/**
 * Direct Preview API
 * Bypasses proxy issues by handling preview generation directly
 */

// Only set JSON header initially for error responses
// Image header will be set later if successful

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight OPTIONS requests
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit();
}

require_once "../config/services.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Content-Type: application/json");
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "error" => "Method not allowed",
    ]);
    exit();
}

try {
    // Get the upload ID and configuration
    $uploadId = $_POST["uploadId"] ?? null;
    $configData = $_POST["data"] ?? null;
    $position = $_GET["position"] ?? "middle";
    $timestamp = $_GET["timestamp"] ?? null;

    if (!$uploadId) {
        throw new Exception("Upload ID is required");
    }

    if (!$configData) {
        throw new Exception("Configuration data is required");
    }

    // Find the uploaded video file
    $uploadDir = "../uploads/";
    $videoPath = null;

    // Check if we have session data for this upload
    if (
        isset($_SESSION["uploaded_video_path"]) &&
        isset($_SESSION["uploaded_video_id"]) &&
        $_SESSION["uploaded_video_id"] === $uploadId
    ) {
        $videoPath = $_SESSION["uploaded_video_path"];
    } else {
        // Try to find the file in uploads directory
        $pattern = $uploadDir . $uploadId . "_*";
        $files = glob($pattern);

        if (!empty($files)) {
            $videoPath = $files[0]; // Take the first match
        }
    }

    if (!$videoPath || !file_exists($videoPath)) {
        throw new Exception(
            "Video file not found for upload ID: " .
                $uploadId .
                ". Checked path: " .
                ($videoPath ?: "none")
        );
    }

    // Get FFmpeg Captions service URL
    $serviceUrl = getServiceUrl("ffmpeg_captions");
    if (!$serviceUrl) {
        throw new Exception("FFmpeg Captions service not configured");
    }

    // Prepare the request to FFmpeg Captions service
    $url = rtrim($serviceUrl, "/") . "/api/captions/preview";

    // Add query parameters
    $queryParams = [];
    if ($position) {
        $queryParams["position"] = $position;
    }
    if ($timestamp && is_numeric($timestamp)) {
        $queryParams["timestamp"] = $timestamp;
    }

    if (!empty($queryParams)) {
        $url .= "?" . http_build_query($queryParams);
    }

    // Prepare multipart form data
    $boundary = "----WebKitFormBoundary" . uniqid();
    $postData = "";

    // Add data field
    $postData .= "--{$boundary}\r\n";
    $postData .= "Content-Disposition: form-data; name=\"data\"\r\n\r\n";
    $postData .= $configData . "\r\n";

    // Add video file
    $fileContent = file_get_contents($videoPath);
    if ($fileContent === false) {
        throw new Exception("Could not read video file: " . $videoPath);
    }

    $fileName = basename($videoPath);
    $mimeType = mime_content_type($videoPath) ?: "video/mp4";

    $postData .= "--{$boundary}\r\n";
    $postData .= "Content-Disposition: form-data; name=\"video\"; filename=\"{$fileName}\"\r\n";
    $postData .= "Content-Type: {$mimeType}\r\n\r\n";
    $postData .= $fileContent . "\r\n";
    $postData .= "--{$boundary}--\r\n";

    // Initialize cURL
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postData,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 300, // 5 minutes
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => [
            "Content-Type: multipart/form-data; boundary={$boundary}",
            "Content-Length: " . strlen($postData),
        ],
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        throw new Exception("Connection failed: " . $error);
    }

    if ($httpCode >= 200 && $httpCode < 300) {
        // Check if response is an image
        if (strpos($contentType, "image/") !== false) {
            // Success - return the image
            // Clear any previous headers and set image headers
            header_remove(); // Remove all previous headers
            header("Content-Type: " . $contentType);
            header("Content-Length: " . strlen($response));
            header("Access-Control-Allow-Origin: *");
            echo $response;
            exit();
        } else {
            // Response is likely JSON with error
            $jsonResponse = json_decode($response, true);
            if (
                $jsonResponse &&
                isset($jsonResponse["success"]) &&
                !$jsonResponse["success"]
            ) {
                throw new Exception(
                    $jsonResponse["error"] ?? "Preview generation failed"
                );
            } else {
                throw new Exception(
                    "Unexpected response format. Content-Type: " . $contentType
                );
            }
        }
    } else {
        // Error response
        $jsonResponse = json_decode($response, true);
        if ($jsonResponse && isset($jsonResponse["error"])) {
            throw new Exception($jsonResponse["error"]);
        } else {
            throw new Exception(
                "Preview generation failed with HTTP " .
                    $httpCode .
                    ". Response: " .
                    substr($response, 0, 500)
            );
        }
    }
} catch (Exception $e) {
    // Ensure JSON header for error responses
    header("Content-Type: application/json");
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage(),
    ]);
    exit();
}
?>
