<?php
/**
 * Services Configuration
 * URLs and settings for AutoCaptions microservices
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Default service URLs
$defaultServices = [
    'transcriptions' => [
        'name' => 'Transcriptions',
        'url' => 'http://localhost:3001',
        'health_endpoint' => '/api/health',
        'description' => 'Audio/video transcription service'
    ],
    'ffmpeg_captions' => [
        'name' => 'FFmpeg Captions',
        'url' => 'http://localhost:3002',
        'health_endpoint' => '/api/captions/health',
        'description' => 'Fast subtitle generation with FFmpeg'
    ],
    'remotion_captions' => [
        'name' => 'Remotion Captions',
        'url' => 'http://localhost:3003',
        'health_endpoint' => '/health',
        'description' => 'Advanced video captions with Remotion'
    ]
];

// Get services configuration from session or use defaults
function getServicesConfig() {
    global $defaultServices;
    
    if (isset($_SESSION['services_config'])) {
        return $_SESSION['services_config'];
    }
    
    return $defaultServices;
}

// Update services configuration
function updateServicesConfig($services) {
    $_SESSION['services_config'] = $services;
    return true;
}

// Get specific service URL
function getServiceUrl($serviceName) {
    $services = getServicesConfig();
    return $services[$serviceName]['url'] ?? null;
}

// Validate service URL format
function isValidServiceUrl($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

// Reset to default configuration
function resetServicesConfig() {
    global $defaultServices;
    $_SESSION['services_config'] = $defaultServices;
    return true;
}
