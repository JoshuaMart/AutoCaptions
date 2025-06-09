<?php
/**
 * API Proxy
 * Proxy requests to AutoCaptions microservices to avoid CORS issues
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/services.php';

/**
 * Proxy a request to a microservice
 */
function proxyRequest($serviceUrl, $endpoint, $method = 'GET', $data = null, $files = null) {
    $url = rtrim($serviceUrl, '/') . '/' . ltrim($endpoint, '/');
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 300); // 5 minutes for video processing
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    // Set HTTP method
    switch (strtoupper($method)) {
        case 'POST':
            curl_setopt($ch, CURLOPT_POST, true);
            break;
        case 'PUT':
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            break;
        case 'DELETE':
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            break;
    }
    
    // Handle file uploads and form data
    if ($files || $data) {
        $postData = [];
        
        // Add regular form data
        if ($data) {
            if (is_array($data)) {
                $postData = array_merge($postData, $data);
            } else {
                $postData['data'] = $data;
            }
        }
        
        // Add files
        if ($files) {
            foreach ($files as $fieldName => $fileInfo) {
                if ($fileInfo['error'] === UPLOAD_ERR_OK) {
                    $postData[$fieldName] = new CURLFile(
                        $fileInfo['tmp_name'],
                        $fileInfo['type'],
                        $fileInfo['name']
                    );
                }
            }
        }
        
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    } elseif ($data && !$files) {
        // JSON data
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return [
            'success' => false,
            'error' => 'Connection failed: ' . $error,
            'http_code' => 0
        ];
    }
    
    return [
        'success' => $httpCode >= 200 && $httpCode < 300,
        'http_code' => $httpCode,
        'content_type' => $contentType,
        'response' => $response
    ];
}

/**
 * Main proxy logic
 */
try {
    // Get request parameters
    $service = $_GET['service'] ?? null;
    $endpoint = $_GET['endpoint'] ?? '';
    $method = $_SERVER['REQUEST_METHOD'];
    
    if (!$service) {
        throw new Exception('Service parameter is required');
    }
    
    // Get service URL
    $serviceUrl = getServiceUrl($service);
    if (!$serviceUrl) {
        throw new Exception('Unknown service: ' . $service);
    }
    
    // Prepare data
    $data = null;
    $files = null;
    
    if ($method === 'POST' || $method === 'PUT') {
        // Handle JSON data
        if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
            $data = file_get_contents('php://input');
        } else {
            // Handle form data
            $data = $_POST;
            $files = $_FILES;
        }
    }
    
    // Make the proxied request
    $result = proxyRequest($serviceUrl, $endpoint, $method, $data, $files);
    
    // Set response headers
    http_response_code($result['http_code']);
    
    if (isset($result['content_type'])) {
        header('Content-Type: ' . $result['content_type']);
    }
    
    // Return response
    if ($result['success']) {
        echo $result['response'];
    } else {
        if (!headers_sent()) {
            header('Content-Type: application/json');
        }
        echo json_encode([
            'success' => false,
            'error' => $result['error'] ?? 'Request failed',
            'http_code' => $result['http_code']
        ], JSON_PRETTY_PRINT);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
