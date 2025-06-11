<?php

declare(strict_types=1);

namespace App\Core;

// Request, Response, Router, and Session classes are now in their own files
// and will be loaded by the autoloader in public/index.php.
use App\Controllers\UploadController; // Import the UploadController
use App\Controllers\TranscriptionController; // Import the TranscriptionController
use App\Controllers\ConfigController; // Import the ConfigController
use App\Services\ConfigManager; // Import the ConfigManager

class Application
{
    private static ?Application $instance = null;
    private static int $getInstanceCallDepth = 0; // Debug counter for recursion
    public ConfigManager $configManager;

    public Request $request;
    public Response $response;
    public Router $router;
    public Session $session;

    private function __construct()
    {
        // Initialize ConfigManager first as other components depend on it
        $this->configManager = new ConfigManager();
        
        $this->setupErrorHandling(); // Uses ConfigManager

        // Initialize core components using their actual classes
        $this->request = new Request(); // Request gathers its own data from globals
        // Response constructor needs security headers config
        $this->response = new Response($this->configManager->get('security.headers', []));
        $this->router = new Router();
        
        // Session constructor needs app.session and the whole security config
        $this->session = new Session(
            $this->configManager->get('app.session', []),
            $this->configManager->get('security', [])
        );

        // Inject Session into ConfigManager now that both are created
        $this->configManager->setSession($this->session);

        date_default_timezone_set($this->configManager->get('app.timezone', 'UTC'));
    }

    public static function getInstance(): Application
    {
        self::$getInstanceCallDepth++;
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5); // Get a small backtrace
        $caller = isset($trace[1]) ? ($trace[1]['class'] ?? 'Global') . '::' . ($trace[1]['function'] ?? 'unknown') : 'Unknown';
        
        error_log("Application::getInstance() called. Depth: " . self::$getInstanceCallDepth . ". Caller: " . $caller);

        if (self::$getInstanceCallDepth > 5) { // Arbitrary limit to stop runaway recursion
            error_log("Application::getInstance() - Recursion depth limit exceeded. Aborting.");
            // Optionally, dump more trace info here
            // debug_print_backtrace();
            throw new \RuntimeException("Application::getInstance() recursion detected from caller: " . $caller);
        }

        if (self::$instance === null) {
            error_log("Application::getInstance() - Instance is null, creating new Application. Depth: " . self::$getInstanceCallDepth);
            self::$instance = new self();
            error_log("Application::getInstance() - New Application created. Depth: " . self::$getInstanceCallDepth);
        }
        
        self::$getInstanceCallDepth--;
        return self::$instance;
    }

    // loadConfigurations() is now handled by ConfigManager

    private function setupErrorHandling(): void
    {
        if ($this->configManager->get('app.debug', false)) {
            ini_set('display_errors', '1');
            ini_set('display_startup_errors', '1');
            error_reporting(E_ALL);
        } else {
            ini_set('display_errors', '0');
            ini_set('display_startup_errors', '0');
            error_reporting(0); // Report no errors to the browser in production

            set_error_handler([$this, 'handleError']);
            set_exception_handler([$this, 'handleException']);
            register_shutdown_function([$this, 'handleShutdown']);
        }

        $logPath = $this->configManager->get('app.logging.path', WEB_REFACTORED_ROOT . '/storage/logs/app.log');
        $logDir = dirname($logPath);
        if (!is_dir($logDir)) {
            if (!mkdir($logDir, 0775, true) && !is_dir($logDir)) {
                // Fallback if mkdir fails, though this should ideally not happen with correct permissions
                error_log("Warning: Could not create log directory: {$logDir}");
                // Use a default log if directory creation fails (e.g., system's default log)
                // This is a last resort to ensure logging still occurs.
            }
        }
         ini_set('error_log', $logPath);
    }

    public function getConfig(string $key, $default = null)
    {
        return $this->configManager->get($key, $default);
    }

    public function run(): void
    {
        // Start the session
        if (!$this->session->start()) {
            // Handle session start failure, e.g., log and display an error
            // For now, we'll log implicitly if Session class does, and proceed.
            // A more robust error page could be shown.
            error_log("Application: Failed to start session.");
            // Potentially die or show a specific error page if session is critical.
        }

        // Define routes (this should ideally be in a separate routes file and loaded by the Router or Application)
        // For now, a simple example:
        $appInstance = $this; // Capture $this for use in closure
        
        // Home page - Upload
        $this->router->get('/', function(Request $request, Response $response) use ($appInstance) {
            // $response object is created by Application and passed by Router, but renderView will use app's $this->response
            $appInstance->renderView('pages/home', [
                'pageTitle' => 'Home - AutoCaptions Refactored'
                // 'pageDescription' will be picked up from home.php or default in main.php
            ]);
        });
        
        // Transcriptions page
        $this->router->get('/transcriptions', function(Request $request, Response $response) use ($appInstance) {
            $appInstance->renderView('pages/transcriptions', [
                'pageTitle' => 'Transcription - AutoCaptions'
            ]);
        });
        
        // Service choice page
        $this->router->get('/service-choice', function(Request $request, Response $response) use ($appInstance) {
            $appInstance->renderView('pages/service-choice', [
                'pageTitle' => 'Choose Service - AutoCaptions'
            ]);
        });

        // API routes for UploadController
        // The plan mentioned POST /api/upload and DELETE /api/upload/{id}
        // The UploadController::deleteCurrentUpload currently works on session data, so no {id} needed for that specific method.
        // If a more generic delete by ID is needed later, a new method and route can be added.
        $this->router->post('/api/upload', [UploadController::class, 'handleUpload']);
        $this->router->delete('/api/upload', [UploadController::class, 'deleteCurrentUpload']);
        $this->router->get('/api/upload/current', [UploadController::class, 'getCurrentUpload']);

        // API routes for TranscriptionController
        $this->router->post('/api/transcription/start', [TranscriptionController::class, 'startTranscription']);
        $this->router->get('/api/transcription/current', [TranscriptionController::class, 'getCurrentTranscription']);
        $this->router->delete('/api/transcription/clear', [TranscriptionController::class, 'clearTranscriptionData']);

        // API routes for ConfigController
        $this->router->get('/api/config/services/status', [ConfigController::class, 'getServicesStatus']);
        $this->router->get('/api/config/services', [ConfigController::class, 'getServicesConfiguration']);
        $this->router->post('/api/config/services', [ConfigController::class, 'updateServiceConfiguration']);
        
        // Additional API route for saving transcription
        $this->router->post('/api/transcription/save', [TranscriptionController::class, 'saveTranscription']);

        // To add other controllers, you would define their routes here.

        // The router will dispatch the request to the appropriate handler.
        try {
            $this->router->dispatch($this->request, $this->response);
        } catch (\Throwable $e) {
            $this->handleException($e);
        }

        // The response sending logic would typically be at the very end,
        // often handled by the Response class itself after content is set by a controller.
        // $this->response->send();
    }

    public function handleError(int $errno, string $errstr, string $errfile, int $errline): bool
    {
        if (!(error_reporting() & $errno)) {
            return false; // Error reporting is suppressed for this error type
        }
        $this->logMessage("Error", $errstr, $errfile, $errline, $errno);
        // In production, we don't want PHP's default handler to run and output errors.
        // If debug is true, PHP's handler might still run depending on other settings.
        return !$this->configManager->get('app.debug', false);
    }

    public function handleException(\Throwable $exception): void
    {
        $this->logMessage(
            "Exception: " . get_class($exception),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getCode(),
            $exception->getTraceAsString()
        );

        if (!headers_sent()) {
            $this->response->setStatusCode(500);
        }

        if ($this->configManager->get('app.debug', false)) {
            // Detailed error for development
            echo "<h1>Unhandled Exception</h1>";
            echo "<p><strong>Type:</strong> " . htmlspecialchars(get_class($exception)) . "</p>";
            echo "<p><strong>Message:</strong> " . htmlspecialchars($exception->getMessage()) . "</p>";
            echo "<p><strong>File:</strong> " . htmlspecialchars($exception->getFile()) . " (Line: " . $exception->getLine() . ")</p>";
            echo "<h2>Stack Trace:</h2><pre>" . htmlspecialchars($exception->getTraceAsString()) . "</pre>";
        } else {
            // Generic error for production
            // Ideally, render a user-friendly error page, e.g., $this->response->render('errors/500');
            echo "<h1>Application Error</h1><p>An unexpected error occurred. Please try again later.</p>";
        }
        exit;
    }

    public function handleShutdown(): void
    {
        $error = error_get_last();
        if ($error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR])) {
            $this->logMessage(
                "Fatal Error",
                $error['message'],
                $error['file'],
                $error['line'],
                $error['type']
            );

            // If not in debug mode and headers haven't been sent, show a generic error.
            if (!$this->configManager->get('app.debug', false) && !headers_sent()) {
                if (http_response_code() === 200) { // Check if status code is still default
                  http_response_code(500);
                }
                // Clear any potentially half-outputted content if possible
                if (ob_get_level() > 0) {
                    ob_end_clean();
                }
                echo "<h1>Application Error</h1><p>A critical error occurred. We have been notified.</p>";
            }
        }
    }

    private function logMessage(string $type, string $message, string $file, int $line, int $levelOrCode, ?string $trace = null): void
    {
        $logEntry = sprintf(
            "[%s] %s: %s in %s on line %d (Level/Code: %d)",
            date('Y-m-d H:i:s'),
            $type,
            $message,
            $file,
            $line,
            $levelOrCode
        );
        if ($trace) {
            $logEntry .= "\nStack trace:\n" . $trace;
        }
        $logEntry .= PHP_EOL;

        $logPath = $this->configManager->get('app.logging.path', WEB_REFACTORED_ROOT . '/storage/logs/app.log');
        error_log($logEntry, 3, $logPath);
    }
    
    // Prevent cloning and unserialization for Singleton
    private function __clone() {}
    public function __wakeup() {
        throw new \Exception("Cannot unserialize a singleton.");
    }

    /**
     * Renders a view within a layout.
     *
     * @param string $viewName The name of the view file (e.g., 'pages/home', 'auth.login'). Dots are directory separators.
     * @param array $data Data to be extracted and made available to the view and layout.
     * @param string $layoutName The name of the layout file in 'src/Views/layouts/'.
     */
    public function renderView(string $viewName, array $data = [], string $layoutName = 'main'): void
    {
        $viewFile = str_replace('.', DIRECTORY_SEPARATOR, $viewName);
        $viewPath = WEB_REFACTORED_ROOT . '/src/Views/' . $viewFile . '.php';
        $layoutPath = WEB_REFACTORED_ROOT . '/src/Views/layouts/' . $layoutName . '.php';

        if (!file_exists($viewPath)) {
            error_log("Application::renderView - View file not found: {$viewPath}");
            $this->response->setStatusCode(500);
            // In debug mode, show detailed error. In production, a generic error page might be rendered.
            if ($this->configManager->get('app.debug', false)) {
                $this->response->setContent("<h1>500 Internal Server Error</h1><p>View file not found: " . htmlspecialchars($viewPath) . "</p>");
            } else {
                $this->response->setContent("<h1>500 Internal Server Error</h1><p>An unexpected error occurred while trying to display the page.</p>");
            }
            $this->response->send();
            return;
        }

        if (!file_exists($layoutPath)) {
            error_log("Application::renderView - Layout file not found: {$layoutPath}");
            $this->response->setStatusCode(500);
            if ($this->configManager->get('app.debug', false)) {
                $this->response->setContent("<h1>500 Internal Server Error</h1><p>Layout file not found: " . htmlspecialchars($layoutPath) . "</p>");
            } else {
                 $this->response->setContent("<h1>500 Internal Server Error</h1><p>An unexpected error occurred while trying to display the page.</p>");
            }
            $this->response->send();
            return;
        }

        // Extract data for the view and layout file.
        // Keys in $data will become variables in the scope of the included files.
        // For example, $data = ['title' => 'My Page'] makes $title available.
        extract($data, EXTR_SKIP); // EXTR_SKIP to not overwrite existing vars like $this

        try {
            ob_start();
            include $viewPath; // View file can set $pageTitle, $pageDescription, etc.
            $content = ob_get_clean(); // This $content variable is used by the layout file.

            // The layout file will now be included. It has access to $content and variables from extract($data).
            ob_start();
            include $layoutPath;
            $finalOutput = ob_get_clean();

            $this->response->setContent($finalOutput);
            $this->response->send();

        } catch (\Throwable $e) {
            // Clean output buffer if an error occurs during view/layout inclusion
            if (ob_get_level() > 0) {
                ob_end_clean();
            }
            // Log the specific view rendering error
            error_log("Application::renderView - Error during view rendering: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
            // Then re-throw or handle as a general application exception
            // For now, let's use the existing exception handler.
            $this->handleException($e); // This will send a 500 response.
        }
    }
}