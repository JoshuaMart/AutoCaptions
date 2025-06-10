<?php
/**
 * Generate Video API
 * Handles video generation with FFmpeg or Remotion services
 */

session_start();

header("Content-Type: application/json");
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
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "error" => "Method not allowed",
    ]);
    exit();
}

try {
    // Get request data
    $input = json_decode(file_get_contents("php://input"), true);
    
    $uploadId = $input["uploadId"] ?? null;
    $service = $input["service"] ?? "ffmpeg"; // ffmpeg or remotion
    $config = $input["config"] ?? null;

    if (!$uploadId) {
        throw new Exception("Upload ID is required");
    }

    if (!$config) {
        throw new Exception("Configuration is required");
    }

    // Find the uploaded video file
    $uploadDir = "../uploads/";
    $videoPath = null;

    // Check session data first
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
            $videoPath = $files[0];
        }
    }

    if (!$videoPath || !file_exists($videoPath)) {
        throw new Exception(
            "Video file not found for upload ID: " . $uploadId
        );
    }

    // Get service URL based on choice
    $serviceKey = $service === "remotion" ? "remotion_captions" : "ffmpeg_captions";
    $serviceUrl = getServiceUrl($serviceKey);
    
    if (!$serviceUrl) {
        throw new Exception(ucfirst($service) . " service not configured");
    }

    // Prepare the API endpoint
    if ($service === "remotion") {
        $url = rtrim($serviceUrl, "/") . "/render";
    } else {
        $url = rtrim($serviceUrl, "/") . "/api/captions/generate";
    }

    // Prepare form data
    $boundary = "----WebKitFormBoundary" . uniqid();
    $postData = "";

    // Add configuration data
    if ($service === "remotion") {
        // Remotion expects separate fields
        $postData .= "--{$boundary}\r\n";
        $postData .= "Content-Disposition: form-data; name=\"transcription\"\r\n\r\n";
        $postData .= json_encode($config["transcriptionData"]) . "\r\n";

        $postData .= "--{$boundary}\r\n";
        $postData .= "Content-Disposition: form-data; name=\"props\"\r\n\r\n";
        $postData .= json_encode($config["props"]) . "\r\n";
    } else {
        // FFmpeg expects single data field
        $postData .= "--{$boundary}\r\n";
        $postData .= "Content-Disposition: form-data; name=\"data\"\r\n\r\n";
        $postData .= json_encode($config) . "\r\n";
    }

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

    // Initialize cURL with longer timeout for video generation
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postData,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 900, // 15 minutes for video generation
        CURLOPT_CONNECTTIMEOUT => 30,
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
        // Check response type
        if (strpos($contentType, "application/json") !== false) {
            // Remotion-style response with download URL
            $jsonResponse = json_decode($response, true);
            if ($jsonResponse && isset($jsonResponse["success"]) && $jsonResponse["success"]) {
                // Store the download URL for later use
                $downloadId = uniqid();
                $_SESSION["download_" . $downloadId] = [
                    "url" => $jsonResponse["downloadUrl"],
                    "service" => $service,
                    "created" => time()
                ];

                echo json_encode([
                    "success" => true,
                    "downloadId" => $downloadId,
                    "downloadUrl" => "/api/download-video.php?id=" . $downloadId,
                    "renderTime" => $jsonResponse["renderTime"] ?? null,
                    "service" => $service
                ]);
            } else {
                throw new Exception($jsonResponse["error"] ?? "Video generation failed");
            }
        } elseif (strpos($contentType, "video/") !== false) {
            // FFmpeg-style direct video response
            // Save the video to a temporary file
            $outputId = uniqid();
            $outputPath = $uploadDir . "output_" . $outputId . ".mp4";
            
            if (file_put_contents($outputPath, $response) === false) {
                throw new Exception("Failed to save generated video");
            }

            // Store video path for download
            $_SESSION["download_" . $outputId] = [
                "path" => $outputPath,
                "service" => $service,
                "created" => time()
            ];

            echo json_encode([
                "success" => true,
                "downloadId" => $outputId,
                "downloadUrl" => "/api/download-video.php?id=" . $outputId,
                "videoSize" => strlen($response),
                "service" => $service
            ]);
        } else {
            throw new Exception("Unexpected response format. Content-Type: " . $contentType);
        }
    } else {
        // Error response
        $jsonResponse = json_decode($response, true);
        if ($jsonResponse && isset($jsonResponse["error"])) {
            throw new Exception($jsonResponse["error"]);
        } else {
            throw new Exception(
                "Video generation failed with HTTP " . $httpCode . 
                ". Response: " . substr($response, 0, 500)
            );
        }
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage(),
    ]);
}
?>
