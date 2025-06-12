<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Application; // Still used for type hint in __construct if we were to pass it, but not for getInstance directly
use App\Core\Session;

class ConfigManager
{
    private const SERVICE_CONFIG_OVERRIDES_SESSION_KEY = 'service_config_overrides';

    private array $config = [];
    private array $baseServiceConfigs = []; // Specifically for 'services' config to manage overrides
    private ?Session $session = null; // Allow session to be initially null

    public function __construct()
    {
        // Session will be injected later via setSession()
        // $app = Application::getInstance(); // REMOVED
        // $this->session = $app->session; // REMOVED
        $this->_loadConfigurations();
    }

    /**
     * Sets the session instance for the ConfigManager.
     * This should be called after Application has initialized both ConfigManager and Session.
     * @param Session $session
     */
    public function setSession(Session $session): void
    {
        $this->session = $session;
    }

    private function _loadConfigurations(): void
    {
        $configPath = WEB_REFACTORED_ROOT . '/config/';
        $configFiles = glob($configPath . '*.php');

        if ($configFiles === false) {
            error_log("ConfigManager: Failed to scan config directory: " . $configPath);
            return;
        }

        foreach ($configFiles as $configFile) {
            $fileName = pathinfo($configFile, PATHINFO_FILENAME);
            try {
                $loadedConfig = require $configFile;
                if (is_array($loadedConfig)) {
                    $this->config[$fileName] = $loadedConfig;
                    if ($fileName === 'services') {
                        // Store a clean copy of base service configurations
                        // These are the service-specific arrays, excluding global keys like 'timeout'
                        foreach($loadedConfig as $key => $value) {
                            if (is_array($value)) { // Actual service entries are arrays
                                 $this->baseServiceConfigs[$key] = $value;
                            }
                        }
                    }
                } else {
                    error_log("ConfigManager: Config file " . $configFile . " did not return an array.");
                }
            } catch (\Throwable $e) {
                 error_log("ConfigManager: Error loading config file " . $configFile . ": " . $e->getMessage());
            }
        }
    }

    /**
     * Get a configuration value using dot notation.
     * This method returns raw configuration from files, not including service overrides.
     *
     * @param string $key The configuration key (e.g., 'app.name', 'services.transcriptions.timeout').
     * @param mixed $default The default value to return if the key is not found.
     * @return mixed The configuration value or the default.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $current = $this->config;

        foreach ($segments as $segment) {
            if (is_array($current) && array_key_exists($segment, $current)) {
                $current = $current[$segment];
            } else {
                return $default;
            }
        }
        return $current;
    }

    /**
     * Get the effective configuration for a specific service, merging base config with session overrides.
     * This is what consuming services (like ServiceManager) should use to get operational configs.
     *
     * @param string $serviceName The name of the service.
     * @return array|null The effective service configuration or null if not found.
     */
    public function getEffectiveServiceConfig(string $serviceName): ?array
    {
        // Get the base configuration for this specific service
        $config = $this->baseServiceConfigs[$serviceName] ?? null;

        if (!$config) {
            return null; // Service not defined in base config files
        }

        // Apply session overrides
        $overrides = [];
        if ($this->session !== null) {
            $overrides = $this->session->get(self::SERVICE_CONFIG_OVERRIDES_SESSION_KEY, []);
        }

        if (isset($overrides[$serviceName])) {
            // Override URL if provided and valid
            if (isset($overrides[$serviceName]['url'])) {
                $overrideUrl = trim((string)$overrides[$serviceName]['url']);
                if (!empty($overrideUrl) && filter_var($overrideUrl, FILTER_VALIDATE_URL)) {
                    $config['url'] = $overrideUrl;
                }
            }
            // Example: Override timeout if allowed and set in session
            // if (isset($overrides[$serviceName]['timeout']) && is_numeric($overrides[$serviceName]['timeout'])) {
            //     $config['timeout'] = (int)$overrides[$serviceName]['timeout'];
            // }
        }

        // Apply global default timeout from 'services.timeout' if no specific timeout is set
        if (!isset($config['timeout'])) {
            $globalServiceTimeout = $this->get('services.timeout'); // e.g., from config/services.php top level
            if ($globalServiceTimeout !== null && is_numeric($globalServiceTimeout)) {
                 $config['timeout'] = (int) $globalServiceTimeout;
            }
        }
        
        return $config;
    }

    /**
     * Get the service configuration intended for display, including the source of the config.
     * Used by UI elements like ConfigController.
     *
     * @param string $serviceName The name of the service.
     * @return array|null The displayable service configuration or null if not found.
     */
    public function getDisplayServiceConfig(string $serviceName): ?array
    {
        $baseConfig = $this->baseServiceConfigs[$serviceName] ?? null;

        if (!$baseConfig) {
            return null;
        }

        $effectiveConfig = $baseConfig; // Start with base config
        $effectiveConfig['name'] = $serviceName; // Add service name for convenience
        $effectiveConfig['source'] = 'file';     // Default source

        $overrides = [];
        if ($this->session !== null) {
            $overrides = $this->session->get(self::SERVICE_CONFIG_OVERRIDES_SESSION_KEY, []);
        }

        if (isset($overrides[$serviceName])) {
            if (isset($overrides[$serviceName]['url'])) {
                $overrideUrl = trim((string)$overrides[$serviceName]['url']);
                // Check if the override URL is non-empty; validity is checked upon setting.
                if (!empty($overrideUrl)) { 
                    $effectiveConfig['url'] = $overrideUrl;
                    $effectiveConfig['source'] = 'session';
                }
            }
            // Future: if other params like timeout are overridden, adjust 'source' accordingly
            // e.g., if ($effectiveConfig['source'] === 'file') $effectiveConfig['source'] = 'session (timeout)';
            // else $effectiveConfig['source'] = 'session (url & timeout)';
        }
        
        // Ensure all common keys are present for display consistency, defaulting from base if not in effective
        $effectiveConfig['url'] = $effectiveConfig['url'] ?? ($baseConfig['url'] ?? null);
        $effectiveConfig['api_prefix'] = $effectiveConfig['api_prefix'] ?? ($baseConfig['api_prefix'] ?? '');
        $effectiveConfig['health_endpoint'] = $effectiveConfig['health_endpoint'] ?? ($baseConfig['health_endpoint'] ?? '');
        
        $currentTimeout = $effectiveConfig['timeout'] ?? ($baseConfig['timeout'] ?? null);
        if ($currentTimeout === null) {
             $globalServiceTimeout = $this->get('services.timeout');
             if ($globalServiceTimeout !== null && is_numeric($globalServiceTimeout)) {
                  $effectiveConfig['timeout'] = (int) $globalServiceTimeout;
             }
        } else {
            $effectiveConfig['timeout'] = (int) $currentTimeout;
        }

        return $effectiveConfig;
    }

    /**
     * Get all service configurations formatted for display.
     *
     * @return array An associative array of service configurations, keyed by service name.
     */
    public function getAllDisplayServiceConfigs(): array
    {
        $displayConfigs = [];
        // Iterate over keys of baseServiceConfigs to ensure we only process actual services
        foreach (array_keys($this->baseServiceConfigs) as $serviceName) {
            $config = $this->getDisplayServiceConfig($serviceName);
            if ($config) { // It should always return a config if serviceName is from baseServiceConfigs keys
                $displayConfigs[$serviceName] = $config;
            }
        }
        return $displayConfigs;
    }

    /**
     * Updates or removes a service URL override in the session.
     *
     * @param string $serviceName The name of the service.
     * @param string|null $newUrl The new URL to set. If null or empty string, the override is removed.
     * @return bool True on success, false on failure (e.g., invalid service name, invalid URL format).
     */
    public function updateServiceUrlOverride(string $serviceName, ?string $newUrl): bool
    {
        if ($this->session === null) {
            error_log("ConfigManager: Session not set. Cannot update service URL override for '{$serviceName}'.");
            return false;
        }

        // Check if the service is defined in the base configuration
        if (!isset($this->baseServiceConfigs[$serviceName])) {
            error_log("ConfigManager: Attempted to update override for non-existent service '{$serviceName}'.");
            return false;
        }

        $currentOverrides = $this->session->get(self::SERVICE_CONFIG_OVERRIDES_SESSION_KEY, []);
        $trimmedNewUrl = ($newUrl !== null) ? trim($newUrl) : null;

        if (empty($trimmedNewUrl)) {
            // Remove the URL override for this service
            if (isset($currentOverrides[$serviceName]['url'])) {
                unset($currentOverrides[$serviceName]['url']);
                // If the service override entry is now empty (no other overridable params), remove the service entry
                if (empty($currentOverrides[$serviceName])) {
                    unset($currentOverrides[$serviceName]);
                }
            }
        } else {
            // Validate the new URL before setting it
            if (filter_var($trimmedNewUrl, FILTER_VALIDATE_URL) === false) {
                error_log("ConfigManager: Invalid URL format provided for service '{$serviceName}': {$trimmedNewUrl}");
                return false;
            }
            // Valid URL, update or set the override
            if (!isset($currentOverrides[$serviceName])) {
                $currentOverrides[$serviceName] = [];
            }
            $currentOverrides[$serviceName]['url'] = $trimmedNewUrl;
        }

        $this->session->set(self::SERVICE_CONFIG_OVERRIDES_SESSION_KEY, $currentOverrides);
        return true;
    }
    
    /**
     * Get all currently set service configuration overrides from the session.
     * @return array
     */
    public function getAllServiceOverrides(): array
    {
        if ($this->session === null) {
            return [];
        }
        return $this->session->get(self::SERVICE_CONFIG_OVERRIDES_SESSION_KEY, []);
    }
}