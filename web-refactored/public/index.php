<?php

declare(strict_types=1);

// Define the root path of the web-refactored application
define('WEB_REFACTORED_ROOT', dirname(__DIR__));

// Basic error reporting for development
// In a production environment, display_errors should be Off
// and errors logged to a file.
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Autoloading - For a real application, Composer's autoloader would be used.
// For now, we'll manually require the Application class.
// This assumes a PSR-4 like structure where App\Core\Application is in src/Core/Application.php
spl_autoload_register(function ($class) {
    // Project-specific namespace prefix
    $prefix = 'App\\';

    // Base directory for the namespace prefix
    $base_dir = WEB_REFACTORED_ROOT . '/src/';

    // Does the class use the namespace prefix?
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // No, move to the next registered autoloader
        return;
    }

    // Get the relative class name
    $relative_class = substr($class, $len);

    // Replace the namespace prefix with the base directory, replace namespace
    // separators with directory separators in the relative class name, append
    // with .php
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    // If the file exists, require it
    if (file_exists($file)) {
        require $file;
    }
});


// Bootstrap the application
// The Application class will handle routing, request processing, etc.
// We will create this class in a subsequent step.
try {
    $app = App\Core\Application::getInstance();
    $app->run();
} catch (Throwable $e) {
    // Basic error handling for critical bootstrap failures
    // A more sophisticated error handler would be part of the Application class
    // or a dedicated error handling service.
    http_response_code(500);
    echo "<h1>Application Error</h1>";
    echo "<p>An unexpected error occurred. Please try again later.</p>";
    if (ini_get('display_errors') === '1') {
        echo "<pre>";
        echo "Error: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "\n";
        echo "File: " . htmlspecialchars($e->getFile(), ENT_QUOTES, 'UTF-8') . "\n";
        echo "Line: " . $e->getLine() . "\n";
        echo "Trace: \n" . htmlspecialchars($e->getTraceAsString(), ENT_QUOTES, 'UTF-8');
        echo "</pre>";
    }
    // Log the error
    error_log("Uncaught exception: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
}

?>