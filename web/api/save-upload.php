<?php
/**
 * Save Upload API
 * Save uploaded video files to disk and return upload ID
 */

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
    if (!isset($_FILES["video"])) {
        throw new Exception("No video file uploaded");
    }

    $uploadedFile = $_FILES["video"];

    // Validate upload
    if ($uploadedFile["error"] !== UPLOAD_ERR_OK) {
        throw new Exception("File upload error: " . $uploadedFile["error"]);
    }

    // Check file size (500MB max)
    $maxSize = 500 * 1024 * 1024; // 500MB
    if ($uploadedFile["size"] > $maxSize) {
        throw new Exception("File too large. Maximum size is 500MB.");
    }

    // Validate file type
    $allowedTypes = [
        "video/mp4",
        "video/quicktime",
        "video/x-msvideo",
        "video/x-matroska",
        "video/webm",
    ];
    $mimeType = mime_content_type($uploadedFile["tmp_name"]);

    if (!in_array($mimeType, $allowedTypes)) {
        // Fallback: check by extension
        $extension = strtolower(
            pathinfo($uploadedFile["name"], PATHINFO_EXTENSION)
        );
        $allowedExtensions = ["mp4", "mov", "avi", "mkv", "webm"];

        if (!in_array($extension, $allowedExtensions)) {
            throw new Exception(
                "Invalid file type. Allowed: MP4, MOV, AVI, MKV, WebM"
            );
        }
    }

    $uploadId = uniqid("video_");
    $uploadDir = "../uploads/";

    // Create directory if needed
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Sanitize filename
    $originalName = basename($uploadedFile["name"]);
    $extension = pathinfo($originalName, PATHINFO_EXTENSION);
    $safeName = preg_replace(
        "/[^a-zA-Z0-9._-]/",
        "_",
        pathinfo($originalName, PATHINFO_FILENAME)
    );
    $fileName = $uploadId . "_" . $safeName . "." . $extension;
    $filePath = $uploadDir . $fileName;

    if (move_uploaded_file($uploadedFile["tmp_name"], $filePath)) {
        $_SESSION["uploaded_video_path"] = $filePath;
        $_SESSION["uploaded_video_id"] = $uploadId;
        $_SESSION["uploaded_video_name"] = $originalName;
        $_SESSION["uploaded_video_size"] = $uploadedFile["size"];

        echo json_encode([
            "success" => true,
            "uploadId" => $uploadId,
            "filename" => $originalName,
            "size" => $uploadedFile["size"],
            "path" => $fileName, // relative path for reference
        ]);
    } else {
        throw new Exception("Failed to save uploaded file");
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage(),
    ]);
}
?>
