<?php

declare(strict_types=1);

namespace App\Core;

class Session
{
    private const FLASH_NEW_KEY = '__flash_new_messages__';
    private const FLASH_OLD_KEY = '__flash_old_messages__';
    private const CSRF_SESSION_KEY = '__csrf_token_value__'; // Internal session key for the CSRF token

    private array $config;
    private array $securityConfig;
    private bool $isStarted = false;

    public function __construct(array $appSessionConfig = [], array $securityConfig = [])
    {
        $this->config = $this->mergeDefaultSessionConfig($appSessionConfig);
        $this->securityConfig = $this->mergeDefaultSecurityConfig($securityConfig);

        // Configure session parameters before attempting to start it.
        // This is crucial for settings like save_path, cookie_params, etc.
        $this->configurePhpSessionSettings();
    }

    private function mergeDefaultSessionConfig(array $appSessionConfig): array
    {
        // Define default session configurations
        $defaults = [
            'driver' => 'file',
            'lifetime' => 120, // minutes
            'path' => '/',
            'domain' => null, // Defaults to current host
            'secure' => false, // Should be true if site is HTTPS
            'http_only' => true,
            'same_site' => 'Lax', // 'Lax', 'Strict', or 'None'
            'storage_path' => defined('WEB_REFACTORED_ROOT') ? WEB_REFACTORED_ROOT . '/storage/sessions' : sys_get_temp_dir(),
            'name' => 'AUTOCAPTIONS_SESSION', // Custom session name
        ];
        return array_merge($defaults, $appSessionConfig);
    }

    private function mergeDefaultSecurityConfig(array $securityConfig): array
    {
        // Define default security-related session configurations
        $defaults = [
            'session_security' => [
                'use_strict_mode' => true,
                'cookie_secure' => null, // If null, determined by 'secure' in app.session or HTTPS status
                'cookie_httponly' => true,
                'cookie_samesite' => 'Lax',
            ],
            'csrf' => [
                'token_name' => '_csrf_token', // Form field name
                'lifetime' => 7200, // seconds
                'regenerate_on_submit' => true,
            ]
        ];
        // Deep merge for nested arrays like 'session_security' and 'csrf'
        return array_replace_recursive($defaults, $securityConfig);
    }

    private function configurePhpSessionSettings(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $this->isStarted = true; // Session already started, perhaps by another part of the system
            return;
        }

        // Set session save path if using file driver
        if (($this->config['driver'] ?? 'file') === 'file' && !empty($this->config['storage_path'])) {
            $savePath = $this->config['storage_path'];
            if (!is_dir($savePath)) {
                if (!mkdir($savePath, 0700, true) && !is_dir($savePath)) {
                    error_log("Session: Failed to create session storage path: " . $savePath);
                    // Fallback or throw an exception if critical
                }
            }
            session_save_path($savePath);
        }

        // Set session name
        if (!empty($this->config['name'])) {
            session_name($this->config['name']);
        }

        // Apply security settings from php.ini equivalents
        ini_set('session.use_trans_sid', '0'); // Disable transparent SID support
        ini_set('session.use_only_cookies', '1'); // Force sessions to only use cookies

        if ($this->securityConfig['session_security']['use_strict_mode'] ?? true) {
            ini_set('session.use_strict_mode', '1'); // Server should not accept session ID if not initialized by server
        }

        // Determine 'secure' flag for session cookies
        $secureCookie = $this->securityConfig['session_security']['cookie_secure'];
        if ($secureCookie === null) {
            $secureCookie = $this->config['secure'] ?? (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on');
        } else {
            $secureCookie = (bool) $secureCookie;
        }

        session_set_cookie_params([
            'lifetime' => (int)$this->config['lifetime'] * 60,
            'path' => $this->config['path'],
            'domain' => $this->config['domain'],
            'secure' => $secureCookie,
            'httponly' => $this->securityConfig['session_security']['cookie_httponly'] ?? true,
            'samesite' => $this->securityConfig['session_security']['cookie_samesite'] ?? 'Lax'
        ]);
    }

    public function start(): bool
    {
        if ($this->isStarted || session_status() === PHP_SESSION_ACTIVE) {
            $this->isStarted = true; // Ensure flag is set if already active
            $this->manageFlashMessages(); // Still manage flash messages
            return true;
        }

        if (headers_sent($file, $line)) {
            error_log("Session: Cannot start session, headers already sent in {$file}:{$line}.");
            return false;
        }

        if (session_start()) {
            $this->isStarted = true;
            $this->manageFlashMessages();
            return true;
        }

        error_log("Session: session_start() failed for unknown reasons.");
        return false;
    }

    public function isActive(): bool
    {
        return $this->isStarted && session_status() === PHP_SESSION_ACTIVE;
    }

    public function set(string $key, $value): void
    {
        if ($this->isActive()) {
            $_SESSION[$key] = $value;
        } else {
            // Optionally log a warning if trying to set session data without an active session
             error_log("Session: Attempted to set '{$key}' without an active session.");
        }
    }

    public function get(string $key, $default = null)
    {
        if ($this->isActive() && array_key_exists($key, $_SESSION)) {
            return $_SESSION[$key];
        }
        return $default;
    }

    public function has(string $key): bool
    {
        return $this->isActive() && array_key_exists($key, $_SESSION);
    }

    public function remove(string $key): void
    {
        if ($this->isActive() && array_key_exists($key, $_SESSION)) {
            unset($_SESSION[$key]);
        }
    }

    public function regenerateId(bool $deleteOldSession = true): bool
    {
        if ($this->isActive()) {
            return session_regenerate_id($deleteOldSession);
        }
        return false;
    }

    public function destroy(): bool
    {
        if (!$this->isActive()) {
            return false;
        }

        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        $destroyed = session_destroy();
        if ($destroyed) {
            $this->isStarted = false;
        }
        return $destroyed;
    }

    private function manageFlashMessages(): void
    {
        if (!$this->isActive()) {
            return;
        }

        // Make messages from previous request (now in FLASH_NEW_KEY) available for current request (move to FLASH_OLD_KEY)
        $newMessages = $this->get(self::FLASH_NEW_KEY, []);
        $this->set(self::FLASH_OLD_KEY, $newMessages);

        // Clear the container for new messages for the current request
        $this->set(self::FLASH_NEW_KEY, []);
    }

    public function setFlash(string $key, $message): void
    {
        if ($this->isActive()) {
            $newMessages = $this->get(self::FLASH_NEW_KEY, []);
            $newMessages[$key] = $message;
            $this->set(self::FLASH_NEW_KEY, $newMessages);
        } else {
            error_log("Session: Attempted to set flash message '{$key}' without an active session.");
        }
    }

    public function getFlash(string $key, $default = null)
    {
        if ($this->isActive()) {
            $oldMessages = $this->get(self::FLASH_OLD_KEY, []);
            if (array_key_exists($key, $oldMessages)) {
                return $oldMessages[$key];
                // Flash messages are not removed upon read; they persist for one full request cycle.
            }
        }
        return $default;
    }

    public function hasFlash(string $key): bool
    {
        if ($this->isActive()) {
            $oldMessages = $this->get(self::FLASH_OLD_KEY, []);
            return array_key_exists($key, $oldMessages);
        }
        return false;
    }

    public function getCsrfTokenName(): string
    {
        return $this->securityConfig['csrf']['token_name'] ?? '_csrf_token';
    }

    public function generateCsrfToken(): string
    {
        if (!$this->isActive()) {
            // Attempt to start session if not active, as CSRF token needs session storage
            if(!$this->start()) {
                error_log("Session: Failed to start session for CSRF token generation.");
                // Return a dummy token or throw an exception, as CSRF protection won't work
                return 'csrf_token_generation_failed_session_inactive';
            }
        }
        // The Security class should ideally handle the actual generation logic.
        // For now, simple generation within Session.
        try {
            $token = bin2hex(random_bytes(32));
        } catch (\Exception $e) {
            // Fallback for random_bytes failure (highly unlikely)
            $token = sha1(uniqid((string)mt_rand(), true));
            error_log("Session: random_bytes failed for CSRF token generation. Error: " . $e->getMessage());
        }
        $this->set(self::CSRF_SESSION_KEY, $token);
        return $token;
    }

    public function getCsrfToken(): ?string
    {
        if (!$this->isActive()) {
            // Attempt to start session to retrieve or generate token
            if(!$this->start()){
                 error_log("Session: Failed to start session for CSRF token retrieval.");
                 return null;
            }
        }
        if (!$this->has(self::CSRF_SESSION_KEY)) {
            return $this->generateCsrfToken(); // Generate if not exists
        }
        return $this->get(self::CSRF_SESSION_KEY);
    }

    public function validateCsrfToken(string $submittedToken): bool
    {
        if (!$this->isActive() || !$this->has(self::CSRF_SESSION_KEY)) {
            return false; // No stored token to compare against
        }

        $storedToken = (string)$this->get(self::CSRF_SESSION_KEY);
        $isValid = hash_equals($storedToken, $submittedToken);

        // Optionally, regenerate token after successful POST/state-changing validation
        if ($isValid && ($this->securityConfig['csrf']['regenerate_on_submit'] ?? true)) {
            // Check if it's a state-changing request (e.g., POST, PUT, DELETE) before regenerating.
            // This logic might be better placed in a controller or middleware.
            // For simplicity here, we regenerate if the config flag is true.
            $this->generateCsrfToken();
        }
        return $isValid;
    }
}