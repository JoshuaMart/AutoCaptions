<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Application;
use CURLFile;

class ServiceManager
{
    private ConfigManager $configManager;
    private int $defaultTimeout;

    public function __construct()
    {
        $app = Application::getInstance();
        $this->configManager = $app->configManager;
        // Get the global default timeout from ConfigManager
        $this->defaultTimeout = $this->configManager->get('services.timeout', 10);
    }

    /**
     * Gets the effective configuration for a service, using ConfigManager.
     * This is used internally by healthCheck and makeRequest.
     *
     * @param string $serviceName
     * @return array|null
     */
    private function getServiceConfig(string $serviceName): ?array
    {
        $config = $this->configManager->getEffectiveServiceConfig($serviceName);
        if (!$config) {
            error_log("ServiceManager: Effective configuration for service '{$serviceName}' not found via ConfigManager.");
            return null;
        }
        return $config;
    }

    /**
     * Gets the service configuration intended for display, using ConfigManager.
     *
     * @param string $serviceName
     * @return array|null
     */
    public function getServiceConfigForDisplay(string $serviceName): ?array
    {
        // Delegate directly to ConfigManager
        return $this->configManager->getDisplayServiceConfig($serviceName);
    }

    private function buildUrl(string $base, string ...$segments): string
    {
        $url = rtrim($base, '/');
        foreach ($segments as $segment) {
            if (!empty($segment)) {
                $url .= '/' . ltrim($segment, '/');
            }
        }
        return $url;
    }

    public function getServiceEndpointUrl(string $serviceName, string $endpointKey, array $pathParams = []): ?string
    {
        $config = $this->getServiceConfig($serviceName);
        if (!$config) {
            return null;
        }

        $baseUrl = $config['url'];
        $apiPrefix = $config['api_prefix'] ?? '';
        
        if (!isset($config['endpoints'][$endpointKey])) {
            error_log("ServiceManager: Endpoint key '{$endpointKey}' not found for service '{$serviceName}'.");
            return null;
        }
        $endpointPath = $config['endpoints'][$endpointKey];

        // Replace path parameters like :id
        foreach ($pathParams as $param => $value) {
            $endpointPath = str_replace(':' . $param, (string)$value, $endpointPath);
        }
        
        return $this->buildUrl($baseUrl, $apiPrefix, $endpointPath);
    }

    /**
     * Performs a health check on a specified service.
     *
     * @param string $serviceName The name of the service (e.g., 'transcriptions').
     * @return array ['status' => 'healthy'|'unhealthy'|'error', 'statusCode' => int|null, 'message' => string, 'details' => mixed|null]
     */
    public function healthCheck(string $serviceName): array
    {
        $config = $this->getServiceConfig($serviceName); // Already gets effective config
        if (!$config) {
            return ['status' => 'error', 'statusCode' => null, 'message' => "Configuration for service '{$serviceName}' not found.", 'details' => null];
        }

        $healthEndpoint = $config['health_endpoint'] ?? null;
        if (!$healthEndpoint) {
            return ['status' => 'error', 'statusCode' => null, 'message' => "Health endpoint not configured for service '{$serviceName}'.", 'details' => null];
        }

        $url = $this->buildUrl($config['url'], $healthEndpoint);
        $timeout = $config['timeout'] ?? $this->defaultTimeout; // Effective config should have timeout

        $response = $this->executeRequest('GET', $url, [], [], [], $timeout);

        if ($response['success'] && $response['statusCode'] >= 200 && $response['statusCode'] < 300) {
            // Response body might already be decoded as an array by executeRequest
            $body = $response['body'];
            if (is_string($body)) {
                $body = json_decode($body, true);
                $jsonDecodeSucceeded = (json_last_error() === JSON_ERROR_NONE);
            } else {
                $jsonDecodeSucceeded = is_array($body);
            }
            
            if ($jsonDecodeSucceeded && isset($body['success']) && $body['success'] === true) {
                 return ['status' => 'healthy', 'statusCode' => $response['statusCode'], 'message' => "Service '{$serviceName}' is healthy.", 'details' => $body];
            } elseif (!$jsonDecodeSucceeded && !empty($response['body'])) {
                // Non-JSON success
                 return ['status' => 'healthy', 'statusCode' => $response['statusCode'], 'message' => "Service '{$serviceName}' responded successfully (non-JSON).", 'details' => $response['body']];
            }
             // Assume healthy if 2xx and content might be minimal or non-JSON
            return ['status' => 'healthy', 'statusCode' => $response['statusCode'], 'message' => "Service '{$serviceName}' responded successfully.", 'details' => $response['body']];
        }

        return [
            'status' => 'unhealthy',
            'statusCode' => $response['statusCode'],
            'message' => "Service '{$serviceName}' health check failed.",
            'details' => $response['error'] ?? $response['body']
        ];
    }

    /**
     * Makes a request to a specified service endpoint.
     *
     * @param string $serviceName Name of the service.
     * @param string $endpointKey Key of the endpoint (from config).
     * @param string $method HTTP method (GET, POST, PUT, DELETE).
     * @param array $data Data for POST/PUT requests (form data or JSON).
     * @param array $files Files for multipart/form-data. ['form_field_name' => '/path/to/file.ext'] or ['form_field_name' => CURLFile_instance]
     * @param array $queryParams Query parameters for the URL.
     * @param array $pathParams Dynamic parts for the endpoint path (e.g., ['id' => 123] for '/users/:id').
     * @param array $headers Additional HTTP headers. ['Header-Name: value']
     * @return array ['success' => bool, 'statusCode' => int|null, 'body' => string|array|null, 'error' => string|null, 'headers' => array|null]
     */
    public function makeRequest(
        string $serviceName,
        string $endpointKey,
        string $method = 'GET',
        array $data = [],
        array $files = [],
        array $queryParams = [],
        array $pathParams = [],
        array $headers = []
    ): array {
        $config = $this->getServiceConfig($serviceName); // Already gets effective config
        if (!$config) {
            return ['success' => false, 'statusCode' => null, 'body' => null, 'error' => "Configuration for service '{$serviceName}' not found.", 'headers' => null];
        }

        $url = $this->getServiceEndpointUrl($serviceName, $endpointKey, $pathParams);
        if (!$url) {
            return ['success' => false, 'statusCode' => null, 'body' => null, 'error' => "Could not build URL for service '{$serviceName}', endpoint '{$endpointKey}'.", 'headers' => null];
        }

        if (!empty($queryParams)) {
            $url .= '?' . http_build_query($queryParams);
        }

        $timeout = $config['timeout'] ?? $this->defaultTimeout; // Effective config should have timeout

        return $this->executeRequest($method, $url, $data, $files, $headers, $timeout);
    }

    private function executeRequest(
        string $method,
        string $url,
        array $data = [],
        array $files = [],
        array $customHeaders = [],
        int $timeout
    ): array {
        $ch = curl_init();
        $method = strtoupper($method);
        $headers = $customHeaders; // Start with custom headers

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout > 5 ? 5 : $timeout); // Shorter connect timeout
        curl_setopt($ch, CURLOPT_HEADER, true); // To get response headers

        // SSL verification - should be true in production, false only for local dev with self-signed certs
        // Consider making this configurable per service or globally
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // TODO: Make configurable for dev
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);    // TODO: Make configurable for dev

        switch ($method) {
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                if (!empty($files)) {
                    $postData = $data; // Start with non-file data
                    foreach ($files as $key => $file) {
                        if (is_string($file) && file_exists($file)) {
                            $postData[$key] = new CURLFile($file, mime_content_type($file) ?: null, basename($file));
                        } elseif ($file instanceof CURLFile) {
                            $postData[$key] = $file;
                        } else {
                             // Log error or skip invalid file entry
                            error_log("ServiceManager: Invalid file entry for key '{$key}' in executeRequest.");
                        }
                    }
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
                    // cURL will set Content-Type to multipart/form-data automatically
                } elseif (!empty($data)) {
                    // Check if data should be JSON encoded
                    $isJsonRequest = false;
                    foreach ($headers as $h) {
                        if (stripos($h, 'Content-Type: application/json') === 0) {
                            $isJsonRequest = true;
                            break;
                        }
                    }
                    if ($isJsonRequest) {
                        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                    } else {
                        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
                        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
                    }
                }
                break;
            case 'PUT':
            case 'PATCH':
            case 'DELETE': // DELETE can have a body, though not always common
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
                if (!empty($data)) {
                    // Similar JSON/form-urlencoded logic as POST
                     $isJsonRequest = false;
                    foreach ($headers as $h) {
                        if (stripos($h, 'Content-Type: application/json') === 0) {
                            $isJsonRequest = true;
                            break;
                        }
                    }
                    if ($isJsonRequest) {
                        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                    } else {
                        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
                         // Ensure Content-Type is set if not already
                        $contentTypeSet = false;
                        foreach ($headers as $h) { if (stripos($h, 'Content-Type:') === 0) { $contentTypeSet = true; break; } }
                        if (!$contentTypeSet) $headers[] = 'Content-Type: application/x-www-form-urlencoded';
                    }
                }
                break;
            case 'GET':
                // Default, no specific options needed for GET beyond URL
                break;
            default:
                curl_close($ch);
                return ['success' => false, 'statusCode' => null, 'body' => null, 'error' => "Unsupported HTTP method: {$method}", 'headers' => null];
        }
        
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, array_unique($headers)); // Use array_unique to avoid duplicate default headers
        }

        $responseContent = curl_exec($ch);
        $curlErrorNo = curl_errno($ch);
        $curlError = curl_error($ch);
        $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        
        $responseHeadersStr = substr($responseContent, 0, $headerSize);
        $responseBody = substr($responseContent, $headerSize);
        
        curl_close($ch);

        $parsedHeaders = $this->parseHeaders($responseHeadersStr);

        if ($curlErrorNo) {
            return ['success' => false, 'statusCode' => $httpStatusCode ?: null, 'body' => null, 'error' => "cURL Error ({$curlErrorNo}): {$curlError}", 'headers' => $parsedHeaders];
        }

        // Try to decode JSON if Content-Type suggests it
        $contentType = $parsedHeaders['content-type'] ?? $parsedHeaders['Content-Type'] ?? '';
        $decodedBody = $responseBody;
        if (stripos($contentType, 'application/json') !== false) {
            $jsonDecoded = json_decode($responseBody, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $decodedBody = $jsonDecoded;
            } else {
                // Log JSON decode error but still return raw body if needed
                error_log("ServiceManager: Failed to decode JSON response from {$url}. Error: " . json_last_error_msg() . ". Body: " . substr($responseBody, 0, 200));
            }
        }
        
        $isSuccess = $httpStatusCode >= 200 && $httpStatusCode < 300;

        return [
            'success' => $isSuccess,
            'statusCode' => $httpStatusCode,
            'body' => $decodedBody,
            'error' => $isSuccess ? null : "HTTP Error {$httpStatusCode}",
            'headers' => $parsedHeaders
        ];
    }

    private function parseHeaders(string $headerStr): array
    {
        $headers = [];
        $lines = explode("\r\n", trim($headerStr));
        // First line is HTTP status, skip it or parse if needed
        array_shift($lines); 
        foreach ($lines as $line) {
            if (str_contains($line, ':')) {
                list($name, $value) = explode(':', $line, 2);
                $name = strtolower(trim($name));
                $value = trim($value);
                if (isset($headers[$name])) {
                    if (!is_array($headers[$name])) {
                        $headers[$name] = [$headers[$name]];
                    }
                    $headers[$name][] = $value;
                } else {
                    $headers[$name] = $value;
                }
            }
        }
        return $headers;
    }
}