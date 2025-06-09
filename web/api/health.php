<?php
/**
 * Health Check API
 * Check the status of all AutoCaptions services
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/services.php';

/**
 * Check if a service is healthy
 */
function checkServiceHealth($serviceUrl, $healthEndpoint) {
    $url = rtrim($serviceUrl, '/') . $healthEndpoint;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return [
            'status' => 'error',
            'message' => 'Connection failed: ' . $error,
            'response_time' => null
        ];
    }
    
    if ($httpCode >= 200 && $httpCode < 300) {
        $data = json_decode($response, true);
        return [
            'status' => 'healthy',
            'message' => 'Service is running',
            'response_time' => curl_getinfo($ch, CURLINFO_TOTAL_TIME),
            'data' => $data
        ];
    }
    
    return [
        'status' => 'unhealthy',
        'message' => 'HTTP ' . $httpCode,
        'response_time' => curl_getinfo($ch, CURLINFO_TOTAL_TIME)
    ];
}

/**
 * Main health check logic
 */
try {
    $services = getServicesConfig();
    $results = [];
    
    foreach ($services as $serviceKey => $serviceInfo) {
        $startTime = microtime(true);
        
        $health = checkServiceHealth(
            $serviceInfo['url'], 
            $serviceInfo['health_endpoint']
        );
        
        $endTime = microtime(true);
        $responseTime = round(($endTime - $startTime) * 1000, 2); // Convert to milliseconds
        
        $results[$serviceKey] = [
            'name' => $serviceInfo['name'],
            'url' => $serviceInfo['url'],
            'status' => $health['status'],
            'message' => $health['message'],
            'response_time' => $responseTime,
            'timestamp' => date('c')
        ];
    }
    
    // Determine overall system status
    $healthyCount = count(array_filter($results, function($service) {
        return $service['status'] === 'healthy';
    }));
    
    $totalCount = count($results);
    $overallStatus = 'healthy';
    
    if ($healthyCount === 0) {
        $overallStatus = 'critical';
    } elseif ($healthyCount < $totalCount) {
        $overallStatus = 'degraded';
    }
    
    $response = [
        'success' => true,
        'overall_status' => $overallStatus,
        'healthy_services' => $healthyCount,
        'total_services' => $totalCount,
        'services' => $results,
        'timestamp' => date('c')
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Health check failed: ' . $e->getMessage(),
        'timestamp' => date('c')
    ], JSON_PRETTY_PRINT);
}
