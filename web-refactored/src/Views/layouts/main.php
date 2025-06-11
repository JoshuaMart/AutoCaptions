<?php
use App\Core\Security; // For CSRF token meta tag if needed later
use App\Core\Application; // To get app name or other config for title

$app = Application::getInstance();
$pageTitle = $pageTitle ?? $app->getConfig('app.name', 'AutoCaptions');
$pageDescription = $pageDescription ?? 'Automatic video captioning service.';

// Define a base path if the application is not at the root of the domain
// This helps in correctly linking assets.
// Assuming .htaccess routes everything through public/index.php,
// asset paths should be relative to the public directory.
// For example, if web-refactored is served from http://localhost/autocaptions/web-refactored/,
// then /assets/css/app.css would resolve to /autocaptions/web-refactored/public/assets/css/app.css
// If index.php is the entry point at the web root of a subdomain or domain, then paths like /assets/... are fine.
// For simplicity, we assume paths are relative from the public root.

// A helper function for asset paths (could be part of a View helper class later)
if (!function_exists('asset')) {
    function asset($path) {
        // This simple version assumes the app runs at the domain root
        // or .htaccess handles paths correctly from the public dir.
        // For subdirectories, a more robust solution involving config or request analysis is needed.
        return '/' . ltrim($path, '/');
    }
}

?>
<!DOCTYPE html>
<html lang="<?= $app->getConfig('app.locale', 'en') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= Security::escapeHtml($pageDescription) ?>">
    
    <?php /* If CSRF tokens are needed for AJAX requests outside of forms:
    <meta name="csrf-token" content="<?= Security::getCsrfToken() ?>">
    */ ?>

    <title><?= Security::escapeHtml($pageTitle) ?></title>

    <link rel="stylesheet" href="<?= asset('assets/css/app.css') ?>">
    
    <!-- Add any other head elements, like favicons, fonts, etc. -->
    <style>
        /* Basic styles to make the page usable before full CSS is in place */
        body { font-family: sans-serif; line-height: 1.6; margin: 0; padding: 0; background-color: #f4f4f4; color: #333; }
        .container { max-width: 1100px; margin: auto; overflow: auto; padding: 0 20px; }
        header { background: #333; color: #fff; padding: 1rem 0; text-align: center; }
        header h1 { margin: 0; }
        main { padding: 20px 0; }
        footer { text-align: center; padding: 20px; background: #333; color: #fff; margin-top: 20px;}
        .hidden { display: none !important; }
        .status-info { color: #31708f; background-color: #d9edf7; border: 1px solid #bce8f1; padding: 10px; margin-bottom: 10px; border-radius: 4px; }
        .status-success { color: #3c763d; background-color: #dff0d8; border: 1px solid #d6e9c6; padding: 10px; margin-bottom: 10px; border-radius: 4px; }
        .status-error { color: #a94442; background-color: #f2dede; border: 1px solid #ebccd1; padding: 10px; margin-bottom: 10px; border-radius: 4px; }
        progress { width: 100%; height: 20px; margin-bottom: 10px; }
        /* Basic service status styling */
        .service-status-list { list-style: none; padding: 0; }
        .service-status-item { border: 1px solid #ddd; padding: 10px; margin-bottom: 5px; border-radius: 4px; background-color: #fff; }
        .service-status-item .service-name { font-weight: bold; display: block; margin-bottom: 5px; }
        .service-status-item .service-health { font-style: italic; }
        .service-status-item .service-details { font-size: 0.9em; color: #555; margin-top: 5px; }
        .service-status-item.status-healthy .service-health { color: green; }
        .service-status-item.status-unhealthy .service-health { color: red; }
        .service-status-item.status-error .service-health { color: orange; }
        .status-loading { font-style: italic; color: #777; }
        .button-retry-status, .button-refresh-status {
            background-color: #5cb85c; color: white; border: none; padding: 8px 12px;
            text-align: center; text-decoration: none; display: inline-block;
            font-size: 14px; margin-top: 10px; cursor: pointer; border-radius: 4px;
        }
        .button-retry-status { background-color: #f0ad4e; }

    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1><?= Security::escapeHtml($app->getConfig('app.name', 'AutoCaptions')) ?></h1>
            <nav>
                <!-- Basic navigation can go here -->
                <!-- Example: <a href="/">Home</a> | <a href="/configure">Configure</a> -->
            </nav>
        </div>
    </header>

    <main class="container">
        <?php
        // This is where the specific page content will be injected.
        // The variable $content should be set by the view rendering logic
        // before including this layout file.
        if (isset($content)) {
            echo $content; // Output the buffered content from the page view
        } else {
            echo "<p>Error: Page content not found.</p>";
        }
        ?>
    </main>

    <footer>
        <div class="container">
            <p>&copy; <?= date('Y') ?> <?= Security::escapeHtml($app->getConfig('app.name', 'AutoCaptions')) ?>. All rights reserved.</p>
        </div>
    </footer>

    <!-- Main application JavaScript file -->
    <!-- type="module" allows using import/export syntax in app.js and its modules -->
    <script type="module" src="<?= asset('assets/js/app.js') ?>"></script>

    <!-- Any other global scripts or third-party libraries can be added here -->
</body>
</html>