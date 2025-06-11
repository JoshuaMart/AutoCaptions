<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\ServiceManager;
use App\Services\ConfigManager;
use App\Core\Application;

class ConfigController
{
    private ServiceManager $serviceManager;
    private ConfigManager $configManager;

    public function __construct()
    {
        $app = Application::getInstance();
        $this->configManager = $app->configManager;
        $this->serviceManager = new ServiceManager(); // ServiceManager now uses ConfigManager internally
    }

    /**
     * Retrieves the health status of all configured microservices.
     *
     * @param Request $request
     * @param Response $response
     */
    public function getServicesStatus(Request $request, Response $response): void
    {
        $serviceConfigsForDisplay = $this->configManager->getAllDisplayServiceConfigs();
        $statuses = [];

        foreach (array_keys($serviceConfigsForDisplay) as $serviceName) {
            // ServiceManager->healthCheck now uses ConfigManager internally for effective config
            $statuses[$serviceName] = $this->serviceManager->healthCheck($serviceName);
        }

        $response->json(
            ['services_status' => $statuses],
            'Service health statuses retrieved.',
            200
        )->send();
    }

    /**
     * Retrieves the current configuration for all services, including session overrides.
     *
     * @param Request $request
     * @param Response $response
     */
    public function getServicesConfiguration(Request $request, Response $response): void
    {
        $effectiveConfigs = $this->configManager->getAllDisplayServiceConfigs();

        $response->json(
            ['services_configuration' => $effectiveConfigs],
            'Service configurations retrieved.',
            200
        )->send();
    }

    /**
     * Updates the configuration (e.g., URL) for specified services.
     * Updates are stored in the session and override the file configuration.
     *
     * @param Request $request Expects a JSON body like: {"services": {"transcriptions": {"url": "http://new-url"}}}
     * @param Response $response
     */
    public function updateServiceConfiguration(Request $request, Response $response): void
    {
        $input = $request->getJsonBody();
        if (!isset($input['services']) || !is_array($input['services'])) {
            $response->errorJson(
                'INVALID_INPUT_FORMAT',
                'Invalid input format. Expected a "services" object.',
                null,
                400
            )->send();
            return;
        }

        $updatedServices = [];
        $errors = [];
        $recognizedServices = array_keys($this->configManager->getAllDisplayServiceConfigs());

        foreach ($input['services'] as $serviceName => $newConfig) {
            if (!in_array($serviceName, $recognizedServices)) {
                $errors[$serviceName] = "Service '{$serviceName}' is not a recognized configurable service.";
                continue;
            }

            // The URL to update; can be null or empty to remove override.
            $newUrl = isset($newConfig['url']) ? trim((string)$newConfig['url']) : null;

            if ($this->configManager->updateServiceUrlOverride($serviceName, $newUrl)) {
                if (empty($newUrl)) {
                    $updatedServices[] = $serviceName . " (override removed)";
                } else {
                    $updatedServices[] = $serviceName . " (URL updated)";
                }
            } else {
                // updateServiceUrlOverride returns false if service unknown or URL invalid
                // ConfigManager logs specific error. Add a general error message here.
                 if ($newUrl !== null && filter_var($newUrl, FILTER_VALIDATE_URL) === false) {
                     $errors[$serviceName] = "Invalid URL format for service '{$serviceName}': {$newUrl}";
                 } else {
                     // This case (unknown service) should ideally be caught by the in_array check above.
                     // So, if it gets here, it's likely an unexpected state or a successful removal treated as error.
                     // The logic in updateServiceUrlOverride should be robust.
                     // If $newUrl was empty/null (removal) and updateServiceUrlOverride succeeded, it's not an error.
                     if (!empty($newUrl)) { // Only count as error if attempting to set an invalid (but non-empty) URL.
                        $errors[$serviceName] = "Failed to update URL for service '{$serviceName}'. Check logs for details.";
                     }
                 }
            }
        }

        if (!empty($errors)) {
            $response->errorJson(
                'CONFIG_UPDATE_VALIDATION_ERROR',
                'Configuration update failed for one or more services.',
                ['errors' => $errors, 'updated_services_list' => $updatedServices],
                400 // Or 422 if considered validation errors
            )->send();
            return;
        }

        // Fetch the new effective configurations to return
        $effectiveConfigs = $this->configManager->getAllDisplayServiceConfigs();
        
        $message = 'Service configurations updated successfully.';
        if (empty($updatedServices) && empty($errors)) {
            $message = 'No configuration changes were applied.';
        } elseif (empty($updatedServices) && !empty($errors)) {
            $message = 'Configuration update failed.'; // Should be caught by error response above
        }

        $response->json(
            [
                'message' => $message,
                'updated_services_list' => $updatedServices,
                'new_effective_configurations' => $effectiveConfigs
            ],
            'Service configuration update processed.',
            200
        )->send();
    }
}
