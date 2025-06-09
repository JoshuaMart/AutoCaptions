<?php
/**
 * Update Configuration API
 * Update service URLs configuration
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
    // Get JSON input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data || !isset($data['services'])) {
        throw new Exception('Invalid request data');
    }
    
    $newServices = $data['services'];
    
    // Validate each service configuration
    foreach ($newServices as $serviceKey => $serviceConfig) {
        if (!isset($serviceConfig['url']) || !isValidServiceUrl($serviceConfig['url'])) {
            throw new Exception("Invalid URL for service: $serviceKey");
        }
        
        // Ensure required fields are present
        if (!isset($serviceConfig['name']) || !isset($serviceConfig['health_endpoint']) || !isset($serviceConfig['description'])) {
            throw new Exception("Missing required fields for service: $serviceKey");
        }
    }
    
    // Update configuration
    if (updateServicesConfig($newServices)) {
        echo json_encode([
            'success' => true,
            'message' => 'Configuration updated successfully',
            'services' => $newServices
        ]);
    } else {
        throw new Exception('Failed to update configuration');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
