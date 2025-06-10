<?php
/**
 * Download Video API
 * Handles downloading of generated videos
 */

session_start();

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight OPTIONS requests
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit();
}

require_once "../config/services.php";

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    header("Content-Type: application/json");
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "error" => "Method not allowed",
    ]);
    exit();
}

try {
    $downloadId = $_GET["id"] ?? null;
    
    if (!$downloadId) {
        throw new Exception("Download ID is required");
    }

    // Check if download exists in session
    $downloadKey = "download_" . $downloadId;
    if (!isset($_SESSION[$downloadKey])) {
        throw new Exception("Download not found or expired");
    }

    $downloadInfo = $_SESSION[$downloadKey];
    
    // Check if download has expired (24 hours)
    if (time() - $downloadInfo["created"] > 86400) {
        unset($_SESSION[$downloadKey]);
        throw new Exception("Download has expired");
    }

    if (isset($downloadInfo["path"])) {
        // Local file download (FFmpeg)
        $filePath = $downloadInfo["path"];
        
        if (!file_exists($filePath)) {
            throw new Exception("Video file not found");
        }

        $fileName = "autocaptions_" . date("Y-m-d_H-i-s") . ".mp4";
        $fileSize = filesize($filePath);
        
        // Set headers for file download
        header("Content-Type: video/mp4");
        header("Content-Disposition: attachment; filename=\"{$fileName}\"");
        header("Content-Length: " . $fileSize);
        header("Accept-Ranges: bytes");
        
        // Stream the file
        $handle = fopen($filePath, "rb");
        if ($handle === false) {
            throw new Exception("Could not open video file");
        }
        
        while (!feof($handle)) {
            echo fread($handle, 8192);
            flush();
        }
        fclose($handle);
        
        // Clean up file after download
        unlink($filePath);
        unset($_SESSION[$downloadKey]);
        
    } elseif (isset($downloadInfo["url"])) {
        // Remote URL download (Remotion)
        $remoteUrl = $downloadInfo["url"];
        
        // Stream the remote file
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $remoteUrl,
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 300,
            CURLOPT_HEADERFUNCTION => function($ch, $header) {
                $headerName = trim(explode(":", $header)[0] ?? "");
                
                // Pass through relevant headers
                if (in_array(strtolower($headerName), ["content-type", "content-length", "accept-ranges"])) {
                    header($header);
                } elseif (strtolower($headerName) === "content-disposition") {
                    // Override with our filename
                    $fileName = "autocaptions_" . date("Y-m-d_H-i-s") . ".mp4";
                    header("Content-Disposition: attachment; filename=\"{$fileName}\"");
                }
                
                return strlen($header);
            },
        ]);
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("Failed to download video: " . $error);
        }
        
        if ($httpCode < 200 || $httpCode >= 300) {
            throw new Exception("Failed to download video: HTTP " . $httpCode);
        }
        
        // Clean up session data
        unset($_SESSION[$downloadKey]);
        
    } else {
        throw new Exception("Invalid download information");
    }

} catch (Exception $e) {
    header("Content-Type: application/json");
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage(),
    ]);
}
?>
