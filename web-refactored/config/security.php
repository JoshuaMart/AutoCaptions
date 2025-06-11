<?php

declare(strict_types=1);

// Security related configurations for the AutoCaptions Web application.
// These settings are used by various components like Core/Security.php, Core/Response.php,
// and services like Services/FileManager.php.

return [

    // Cross-Site Request Forgery (CSRF) Protection
    // Used by App\Core\Security to generate and validate tokens.
    'csrf' => [
        'token_name' => '_csrf_token',          // Name of the CSRF token field in forms and session.
        'cookie_name' => 'csrf_protection',     // Optional: if storing token in a cookie for JS access.
        'lifetime' => 7200,                     // Token lifetime in seconds (e.g., 2 hours).
        'regenerate_on_submit' => true,         // Regenerate token after each successful state-changing request.
        'protect_ajax' => true,                 // Whether AJAX requests should also be protected.
                                                // If true, JavaScript needs to include the token in requests.
        'samesite_cookie_attribute' => 'Lax',   // SameSite attribute for the CSRF cookie ('Lax', 'Strict', 'None').
                                                // 'None' requires 'Secure' attribute.
    ],

    // HTTP Security Headers
    // These are default values that App\Core\Response will attempt to set.
    // Some headers might be better fine-tuned based on specific responses.
    'headers' => [
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'DENY', // As per REFACTORING.md, 'SAMEORIGIN' is also common.
        'X-XSS-Protection' => '1; mode=block',
        'Referrer-Policy' => 'strict-origin-when-cross-origin', // A good default for privacy and security.
        // 'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains', // Enable HSTS if HTTPS is enforced site-wide. Add 'preload' if preloading.
        // 'Permissions-Policy' => "geolocation=(), microphone=(), camera=()", // Define feature policies.

        // Content Security Policy (CSP)
        // CSP is a powerful security layer but requires careful configuration.
        // The App\Core\Response class should build the CSP header string from these directives.
        'Content-Security-Policy' => [
            'enabled' => true,          // Master switch for CSP.
            'report_only' => false,     // Set to true to report violations without enforcing policy (for testing).
            'directives' => [
                'default-src' => ["'self'"],
                'script-src' => ["'self'"], // Add hashes/nonces or specific domains if not using 'unsafe-inline'.
                                            // For the planned JS modules, this should be fine.
                                            // If using inline event handlers or script tags, "'unsafe-inline'" might be needed initially.
                'style-src' => ["'self'", "'unsafe-inline'"], // Tailwind CSS might use inline styles. If so, this is needed.
                                                              // Otherwise, try to remove 'unsafe-inline'.
                'img-src' => ["'self'", "data:"], // 'data:' for inline images (e.g., small icons).
                'font-src' => ["'self'"], // Add domains if using web fonts from CDNs.
                'connect-src' => ["'self'"], // For API calls. Add service URLs if on different origins.
                                             // e.g., 'http://localhost:3001', 'http://localhost:3002'
                'form-action' => ["'self'"], // Restricts where forms can submit to.
                'frame-ancestors' => ["'none'"], // Disallows embedding in iframes. Use "'self'" if needed.
                'object-src' => ["'none'"], // Disallows <object>, <embed>, <applet>.
                'base-uri' => ["'self'"],
                'upgrade-insecure-requests' => true, // If site is HTTPS, auto-upgrade HTTP requests.
                // 'report-uri' => '/csp-violation-report-endpoint', // Endpoint to send violation reports.
            ],
        ],
    ],

    // File Upload Security Settings
    // Used by App\Services\FileManager and App\Core\Validator.
    'upload_security' => [
        // Define allowed MIME types for file uploads. This is crucial for security.
        'allowed_mime_types' => [
            // Video formats based on service READMEs
            'video/mp4',        // .mp4
            'video/quicktime',  // .mov
            'video/x-msvideo',  // .avi (less common for web)
            'video/x-matroska', // .mkv
            'video/webm',       // .webm
        ],
        // Maximum file size in bytes. Should be consistent with PHP settings (upload_max_filesize, post_max_size)
        // and backend service limits. The transcription service mentions 500MB.
        'max_file_size_bytes' => 500 * 1024 * 1024, // 500 MB
        'sanitize_filenames' => true, // Whether to sanitize uploaded filenames to prevent directory traversal etc.
        'default_upload_path' => dirname(__DIR__) . '/storage/uploads', // Ensure this path is not web-accessible.
    ],

    // Session Security Settings
    // Used by App\Core\Session. These complement settings in config/app.php.
    'session_security' => [
        'use_strict_mode' => true,      // PHP's session.use_strict_mode.
        'cookie_secure' => null,        // If null, determined by 'secure' in app.php session config or HTTPS status.
                                        // Set to true to always require HTTPS for session cookies.
        'cookie_httponly' => true,      // Session cookie accessible only via HTTP, not JavaScript.
        'cookie_samesite' => 'Lax',     // CSRF protection for session cookies. ('Lax', 'Strict', 'None').
        'regenerate_id_on_login' => true, // Regenerate session ID upon user authentication (if applicable).
        'lock_to_user_agent' => true,   // Basic session hijacking prevention: check User-Agent.
        'lock_to_ip_address' => false,  // More restrictive: check IP. Can cause issues with dynamic IPs.
                                        // If true, consider how many octets of IP to check (e.g., first 3).
    ],

    // Input Validation Defaults
    // May be used by App\Core\Validator or specific controllers.
    'validation' => [
        'stop_on_first_failure' => false, // Whether validator should stop on the first error or collect all.
    ],
];
