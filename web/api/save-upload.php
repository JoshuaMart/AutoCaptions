// /api/save-upload.php
<?php
header("Content-Type: application/json");
require_once "../config/services.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    exit();
}

try {
    if (!isset($_FILES["video"])) {
        throw new Exception("No video file uploaded");
    }

    $uploadedFile = $_FILES["video"];
    $uploadId = uniqid("video_");
    $uploadDir = "../uploads/";

    // Créer le dossier si nécessaire
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $filePath = $uploadDir . $uploadId . "_" . basename($uploadedFile["name"]);

    if (move_uploaded_file($uploadedFile["tmp_name"], $filePath)) {
        session_start();
        $_SESSION["uploaded_video_path"] = $filePath;
        $_SESSION["uploaded_video_id"] = $uploadId;

        echo json_encode([
            "success" => true,
            "uploadId" => $uploadId,
            "filename" => $uploadedFile["name"],
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
