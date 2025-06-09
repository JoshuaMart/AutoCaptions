<?php
/**
 * Reset Configuration API
 * Reset service URLs to default values
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/services.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed'
    ]);
    exit();
}

try {
    // Reset to default configuration
    if (resetServicesConfig()) {
        $services = getServicesConfig();
        
        echo json_encode([
            'success' => true,
            'message' => 'Configuration reset to defaults successfully',
            'services' => $services
        ]);
    } else {
        throw new Exception('Failed to reset configuration');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
