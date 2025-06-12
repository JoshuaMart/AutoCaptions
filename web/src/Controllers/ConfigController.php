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
            $healthCheck = $this->serviceManager->healthCheck($serviceName);
            $config = $serviceConfigsForDisplay[$serviceName];
            
            $statuses[$serviceName] = [
                'name' => ucfirst(str_replace('-', ' ', $serviceName)),
                'status' => $healthCheck['status'],
                'message' => $healthCheck['message'],
                'url' => $config['url'],
                'statusCode' => $healthCheck['statusCode'],
                'details' => $healthCheck['details'] ?? null,
                'response_time' => $healthCheck['response_time'] ?? null
            ];
        }

        // Calculate overall status
        $healthyCount = count(array_filter($statuses, fn($s) => $s['status'] === 'healthy'));
        $totalCount = count($statuses);
        
        $overallStatus = 'healthy';
        if ($healthyCount === 0) {
            $overallStatus = 'critical';
        } elseif ($healthyCount < $totalCount) {
            $overallStatus = 'degraded';
        }

        $response->json(
            [
                'services' => $statuses,
                'overall_status' => $overallStatus,
                'healthy_services' => $healthyCount,
                'total_services' => $totalCount
            ],
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
        $servicesData = [];

        foreach ($effectiveConfigs as $serviceName => $config) {
            $servicesData[$serviceName] = [
                'name' => ucfirst(str_replace('-', ' ', $serviceName)),
                'description' => $this->getServiceDescription($serviceName),
                'url' => $config['url'],
                'effective_url' => $config['url'],
                'source' => $config['source'],
                'api_prefix' => $config['api_prefix'] ?? '',
                'health_endpoint' => $config['health_endpoint'] ?? '',
                'timeout' => $config['timeout'] ?? 10,
                'default_port' => $this->getDefaultPort($serviceName)
            ];
        }

        $response->json(
            $servicesData,
            'Service configurations retrieved.',
            200
        )->send();
    }

    private function getServiceDescription(string $serviceName): string
    {
        $descriptions = [
            'transcriptions' => 'Audio/video transcription service using AI models',
            'ffmpeg-captions' => 'Fast caption generation using FFmpeg and ASS subtitles',
            'remotion-captions' => 'Advanced caption rendering with Remotion effects'
        ];
        
        return $descriptions[$serviceName] ?? 'Microservice for AutoCaptions';
    }

    private function getDefaultPort(string $serviceName): string
    {
        $ports = [
            'transcriptions' => '3001',
            'ffmpeg-captions' => '3002',
            'remotion-captions' => '3003'
        ];
        
        return $ports[$serviceName] ?? '3000';
    }

    /**
     * Updates the configuration (e.g., URL) for specified services.
     * Updates are stored in the session and override the file configuration.
     *
     * @param Request $request Expects a JSON body like: {"transcriptions": {"url": "http://new-url"}, ...}
     * @param Response $response
     */
    public function updateServiceConfiguration(Request $request, Response $response): void
    {
        $input = $request->getJsonBody();
        if (!is_array($input)) {
            $response->errorJson(
                'INVALID_INPUT_FORMAT',
                'Invalid input format. Expected an object with service configurations.',
                null,
                400
            )->send();
            return;
        }

        $updatedServices = [];
        $errors = [];
        $recognizedServices = array_keys($this->configManager->getAllDisplayServiceConfigs());

        foreach ($input as $serviceName => $newConfig) {
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
