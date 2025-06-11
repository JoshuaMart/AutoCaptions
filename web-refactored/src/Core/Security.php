<?php

declare(strict_types=1);

namespace App\\Core;

// Ensure Application and Session classes are available.
// In a real setup, Composer's autoloader handles this.
// These are placeholders if not already loaded, but Application.php should load Session.php.
if (!class_exists(\'App\\Core\\Application\')) {
    // This is a simplified placeholder. The actual Application class is more complex.
    class Application {
        private static $instance;
        public $session; // Placeholder for Session object
        public static function getInstance() {
            if (null === static::$instance) {
                // In a real scenario, getInstance would return a fully initialized Application
                // For Security class, we primarily need access to the session and config.
                // This placeholder instantiation is not fully correct for a running app
                // but allows Security methods to be drafted.
                static::$instance = new self();
                // A minimal session mock-up if actual Session class isn't loaded/available
                if (!class_exists(\'App\\Core\\Session\')) {
                    static::$instance->session = new class {
                        public function getCsrfTokenName(): string { return \'_csrf_token\'; }
                        public function getCsrfToken(): string { return \'dummy_csrf_token_from_security_placeholder\'; }
                        public function validateCsrfToken(string $token): bool { return $token === \'dummy_csrf_token_from_security_placeholder\'; }
                    };
                } else {
                     // If Session class is available, it should be instantiated and set properly by Application.
                     // This direct new Session() might miss necessary configurations.
                     // The real Application::getInstance()->session should be used.
                    // static::$instance->session = new Session([], []); // This needs proper config
                }
            }
            return static::$instance;
        }
        public function getConfig(string $key, $default = null) {
            // Simplified config access for placeholder
            if ($key === \'security.csrf\') return [\'token_name\' => \'_csrf_token\'];
            return $default;
        }
    }
}


class Security
{
    /**
     * Get the active Session instance from the Application.
     *
     * @return Session
     * @throws \\RuntimeException If Application or Session instance is not available.
     */
    private static function getSession(): Session
    {
        $app = Application::getInstance();
        if (!isset($app->session) || !$app->session instanceof Session) {
            // This case should ideally not happen in a correctly bootstrapped application.
            // The Application class is responsible for initializing the Session.
            // Throw an exception or log a critical error.
            throw new \\RuntimeException("Session service is not available or not initialized in Application.");
        }
        return $app->session;
    }

    /**
     * Get the configured CSRF token name.
     * This is the name of the form field that should contain the CSRF token.
     *
     * @return string The CSRF token name.
     */
    public static function getCsrfTokenName(): string
    {
        return self::getSession()->getCsrfTokenName();
    }

    /**
     * Get the current CSRF token.
     * If a token doesn\'t exist in the session, one will be generated and stored.
     *
     * @return string The CSRF token.
     */
    public static function getCsrfToken(): string
    {
        return self::getSession()->getCsrfToken();
    }

    /**
     * Validate a submitted CSRF token against the one stored in the session.
     *
     * @param string $submittedToken The CSRF token submitted by the user.
     * @return bool True if the token is valid, false otherwise.
     */
    public static function validateCsrfToken(string $submittedToken): bool
    {
        if (empty($submittedToken)) {
            return false;
        }
        return self::getSession()->validateCsrfToken($submittedToken);
    }

    /**
     * Generate the HTML hidden input field for CSRF token.
     *
     * @return string The HTML string for the CSRF hidden input field.
     */
    public static function getCsrfInput(): string
    {
        $tokenName = self::escapeHtml(self::getCsrfTokenName());
        $tokenValue = self::escapeHtml(self::getCsrfToken());
        return sprintf(\'<input type="hidden" name="%s" value="%s">\', $tokenName, $tokenValue);
    }

    /**
     * Escape HTML special characters in a string.
     * A utility function to prevent XSS vulnerabilities when outputting data.
     *
     * @param string|null $data The string to escape. If null, an empty string is returned.
     * @param string $encoding The character encoding. Defaults to \'UTF-8\'.
     * @return string The escaped string.
     */
    public static function escapeHtml(?string $data, string $encoding = \'UTF-8\'): string
    {
        if ($data === null) {
            return \'\';
        }
        return htmlspecialchars($data, ENT_QUOTES | ENT_SUBSTITUTE, $encoding);
    }

    /**
     * Sanitize a string by removing or encoding potentially malicious characters.
     * This is a basic sanitizer; for complex scenarios, a library might be better.
     *
     * @param string $input The string to sanitize.
     * @param string $mode \'strip\' to remove tags, \'encode\' to HTML encode.
     * @return string The sanitized string.
     */
    public static function sanitizeString(string $input, string $mode = \'encode\'): string
    {
        if ($mode === \'strip\') {
            return strip_tags($input);
        }
        // Default to encoding
        return self::escapeHtml($input);
    }

    /**
     * Generates a secure random string.
     *
     * @param int $length The length of the random string to generate.
     * @return string The generated random string.
     * @throws \\Exception If a cryptographically secure source of randomness is not available.
     */
    public static function generateRandomString(int $length = 32): string
    {
        if ($length <= 0) {
            return \'\';
        }
        return bin2hex(random_bytes($length / 2 + 1)); // +1 to handle odd lengths
    }
}
