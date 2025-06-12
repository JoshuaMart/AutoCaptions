<?php

declare(strict_types=1);

return [
    'name' => 'AutoCaptions Web',
    'env' => 'development', // 'development', 'production', 'testing'
    'debug' => true, // Set to false in production
    'url' => 'http://localhost', // Base URL of the application

    'timezone' => 'UTC',

    // Default paths (can be overridden by environment variables or other configs)
    'paths' => [
        'root' => dirname(__DIR__),
        'public' => dirname(__DIR__) . '/public',
        'storage' => dirname(__DIR__) . '/storage',
        'config' => __DIR__,
        'views' => dirname(__DIR__) . '/src/Views',
    ],

    // Logging configuration
    'logging' => [
        'channel' => 'default', // Default log channel
        'path' => dirname(__DIR__) . '/storage/logs/app.log',
        'level' => 'debug', // Minimum log level (debug, info, warning, error, critical)
    ],

    // Session configuration
    'session' => [
        'driver' => 'file', // 'file' or 'database' (if implemented)
        'lifetime' => 120, // Session lifetime in minutes
        'expire_on_close' => false,
        'encrypt' => false, // Whether to encrypt session data
        'path' => '/',
        'domain' => null,
        'secure' => false, // Set to true if using HTTPS
        'http_only' => true,
        'same_site' => 'lax', // 'lax', 'strict', or 'none'
        'storage_path' => dirname(__DIR__) . '/storage/sessions',
    ],

    // Default language/locale
    'locale' => 'en',
    'fallback_locale' => 'en',
];