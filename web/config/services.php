<?php

declare(strict_types=1);

// Configuration for backend microservices
//
// The ServiceManager will use these settings to communicate with the respective services.
// URLs should be the base URLs of the services.
//
// In a Docker environment, these might be service names like 'http://transcriptions:3001'.
// For local development, they would typically be 'http://localhost:PORT'.

return [
    "timeout" => 10, // Default timeout in seconds for API requests to services

    "transcriptions" => [
        "url" =>
            getenv("TRANSCRIPTIONS_SERVICE_URL") ?:
            "http://transcriptions:3001",
        // 'url' => 'http://transcriptions:3001', // Example for Docker
        "api_prefix" => "/api",
        "health_endpoint" => "/api/health", // Or just '/health' depending on the service
        "endpoints" => [
            "transcribe" => "/transcribe",
            "services" => "/services",
        ],
        "timeout" => 300, // Specific timeout for transcription service (can be long)
    ],

    "ffmpeg-captions" => [
        "url" =>
            getenv("FFMPEG_CAPTIONS_SERVICE_URL") ?:
            "http://ffmpeg-captions:3002",
        // 'url' => 'http://ffmpeg-captions:3002', // Example for Docker
        "api_prefix" => "/api/captions",
        "health_endpoint" => "/api/captions/health",
        "endpoints" => [
            "generate" => "/generate",
            "preview" => "/preview",
            "presets" => "/presets",
            "preset_detail" => "/presets/:preset",
            "fonts" => "/fonts",
            "font_variants" => "/fonts/:family/variants",
        ],
        "timeout" => 180, // Specific timeout
    ],

    "remotion-captions" => [
        "url" =>
            getenv("REMOTION_CAPTIONS_SERVICE_URL") ?:
            "http://remotion-captions:3003",
        // 'url' => 'http://remotion-captions:3003', // Example for Docker
        "api_prefix" => "", // Assuming API endpoints are at root or have specific paths
        "health_endpoint" => "/health",
        "endpoints" => [
            "render" => "/render",
            "download" => "/download", // :uploadId will be appended
        ],
        "timeout" => 300, // Remotion rendering can also take time
    ],

    // You can add more services here as needed
    // 'another_service' => [
    // 'url' => 'http://localhost:3004',
    // 'api_prefix' => '/api/v1',
    // 'health_endpoint' => '/status',
    // 'timeout' => 15,
    // ],
];
