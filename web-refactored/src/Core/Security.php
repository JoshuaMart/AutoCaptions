<?php

declare(strict_types=1);

namespace App\Core;

// The Session class will be autoloaded if needed for typehinting.
// The Application class will be autoloaded when Application::getInstance() is called.

class Security
{
    /**
     * Get the active Session instance from the Application.
     * This method relies on Application::getInstance() which should return
     * a fully initialized Application object, including its Session property.
     *
     * @return Session
     * @throws \RuntimeException If Application or Session instance is not available or not correctly initialized.
     */
    private static function getSession(): Session
    {
        // This will use the autoloader to load Application.php if not already loaded.
        $app = Application::getInstance();

        // At this point, $app->session should be an initialized Session object
        // because Application's constructor is responsible for creating it.
        if (!isset($app->session) || !$app->session instanceof Session) {
            // This indicates a problem in the Application's bootstrap sequence
            // or that getSession() is being called too early for session-dependent operations.
            error_log("Security::getSession() - Session object not found or invalid in Application instance.");
            throw new \RuntimeException("L'instance de Session n'est pas disponible ou non initialisée dans Application.");
        }
        return $app->session;
    }

    /**
     * Get the configured CSRF token name.
     *
     * @return string The CSRF token name.
     */
    public static function getCsrfTokenName(): string
    {
        return self::getSession()->getCsrfTokenName();
    }

    /**
     * Get the current CSRF token.
     * Session::getCsrfToken() handles generation if the token doesn't exist.
     *
     * @return string The CSRF token.
     * @throws \RuntimeException If the token cannot be retrieved or generated.
     */
    public static function getCsrfToken(): string
    {
        $session = self::getSession();
        $token = $session->getCsrfToken(); // This method in Session should ensure a token is returned if session is active

        if ($token === null) {
            // This might happen if the session itself could not be started properly
            // and thus couldn't store/generate a token.
            error_log("Security::getCsrfToken() - CSRF token is null, indicating session issue.");
            throw new \RuntimeException("Impossible de récupérer ou de générer le jeton CSRF. La session pourrait ne pas être active.");
        }
        return $token;
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
        // getSession() might throw if session isn't available, which is fine.
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
        $tokenValue = self::escapeHtml(self::getCsrfToken()); // This will call getSession()
        return sprintf('<input type="hidden" name="%s" value="%s">', $tokenName, $tokenValue);
    }

    /**
     * Escape HTML special characters in a string.
     * This method is safe to call even if the full application/session isn't bootstrapped,
     * as it does not rely on getSession().
     *
     * @param string|null $data The string to escape. If null, an empty string is returned.
     * @param string $encoding The character encoding. Defaults to 'UTF-8'.
     * @return string The escaped string.
     */
    public static function escapeHtml(?string $data, string $encoding = 'UTF-8'): string
    {
        if ($data === null) {
            return '';
        }
        return htmlspecialchars($data, ENT_QUOTES | ENT_SUBSTITUTE, $encoding);
    }

    /**
     * Sanitize a string by removing or encoding potentially malicious characters.
     * This method is safe to call even if the full application/session isn't bootstrapped.
     *
     * @param string $input The string to sanitize.
     * @param string $mode 'strip' to remove tags, 'encode' to HTML encode.
     * @return string The sanitized string.
     */
    public static function sanitizeString(string $input, string $mode = 'encode'): string
    {
        if ($mode === 'strip') {
            return strip_tags($input);
        }
        // Default to encoding
        return self::escapeHtml($input);
    }

    /**
     * Generates a secure random string.
     * This method is safe to call even if the full application/session isn't bootstrapped.
     *
     * @param int $length The desired length of the random string.
     * @return string The generated random string.
     * @throws \Exception If a cryptographically secure source of randomness is not available or length is invalid.
     */
    public static function generateRandomString(int $length = 32): string
    {
        if ($length <= 0) {
            // Or throw an InvalidArgumentException
            error_log("Security::generateRandomString() - Invalid length requested: " . $length);
            return ''; // Or throw
        }
        // Each byte from random_bytes() is represented by two hex characters.
        // So, we need $length / 2 bytes. Use ceil to handle odd lengths correctly.
        $byteLength = (int) ceil($length / 2);
        if ($byteLength <=0) { // Should not happen if $length > 0
            return '';
        }
        $randomBytes = random_bytes($byteLength);
        // Convert to hex and then trim to the exact desired length if $length was odd.
        return substr(bin2hex($randomBytes), 0, $length);
    }
}