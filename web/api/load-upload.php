<?php
/**
 * Load Upload API
 * Retrieve a previously saved video file by uploadId
 */

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight OPTIONS requests
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "error" => "Method not allowed",
    ]);
    exit();
}

try {
    $uploadId = $_GET["uploadId"] ?? null;

    if (!$uploadId) {
        throw new Exception("Upload ID is required");
    }

    // Check if we have session data for this upload
    if (
        !isset($_SESSION["uploaded_video_path"]) ||
        !isset($_SESSION["uploaded_video_id"]) ||
        $_SESSION["uploaded_video_id"] !== $uploadId
    ) {
        // Try to find the file in uploads directory
        $uploadDir = "../uploads/";
        $pattern = $uploadDir . $uploadId . "_*";
        $files = glob($pattern);

        if (empty($files)) {
            throw new Exception(
                "Video file not found for upload ID: " . $uploadId
            );
        }

        $filePath = $files[0]; // Take the first match
    } else {
        $filePath = $_SESSION["uploaded_video_path"];
    }

    // Verify file exists
    if (!file_exists($filePath)) {
        throw new Exception("Video file not found on disk: " . $filePath);
    }

    // Get file info
    $fileSize = filesize($filePath);
    $mimeType = mime_content_type($filePath);

    // If mime type detection fails, guess from extension
    if (!$mimeType) {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $mimeTypes = [
            "mp4" => "video/mp4",
            "mov" => "video/quicktime",
            "avi" => "video/x-msvideo",
            "mkv" => "video/x-matroska",
            "webm" => "video/webm",
        ];
        $mimeType = $mimeTypes[$extension] ?? "video/mp4";
    }

    // Set headers for file download
    header("Content-Type: " . $mimeType);
    header("Content-Length: " . $fileSize);
    header(
        'Content-Disposition: inline; filename="' . basename($filePath) . '"'
    );
    header("Cache-Control: no-cache, must-revalidate");
    header("Expires: 0");

    // Output file content
    readfile($filePath);
} catch (Exception $e) {
    http_response_code(404);
    header("Content-Type: application/json");
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage(),
    ]);
}
?>
