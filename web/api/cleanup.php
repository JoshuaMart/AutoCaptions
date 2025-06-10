<?php
/**
 * Cleanup Script
 * Remove old uploaded files (older than 24 hours)
 * Call this script periodically via cron or run manually
 */

function cleanupOldUploads($uploadDir = '../uploads/', $maxAge = 86400) {
    $now = time();
    $deletedCount = 0;
    $totalSize = 0;
    
    if (!is_dir($uploadDir)) {
        return [
            'success' => true,
            'message' => 'Upload directory does not exist',
            'deleted' => 0,
            'freed_space' => 0
        ];
    }
    
    $files = glob($uploadDir . 'video_*');
    
    foreach ($files as $file) {
        if (is_file($file)) {
            $fileAge = $now - filemtime($file);
            
            if ($fileAge > $maxAge) {
                $fileSize = filesize($file);
                if (unlink($file)) {
                    $deletedCount++;
                    $totalSize += $fileSize;
                }
            }
        }
    }
    
    return [
        'success' => true,
        'message' => "Cleanup completed",
        'deleted' => $deletedCount,
        'freed_space' => $totalSize,
        'freed_space_mb' => round($totalSize / (1024 * 1024), 2)
    ];
}

// If called directly (not included), run cleanup
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    header('Content-Type: application/json');
    
    $maxAge = $_GET['max_age'] ?? 86400; // 24 hours default
    $result = cleanupOldUploads('../uploads/', intval($maxAge));
    
    echo json_encode($result, JSON_PRETTY_PRINT);
}
?>
