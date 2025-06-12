<?php

declare(strict_types=1);

namespace App\Core;

class Request
{
    private array $getParams;
    private array $postParams;
    private array $serverParams;
    private array $files;
    private array $cookies;
    private array $headers;
    private ?array $jsonBody = null;
    private string $method;
    private string $uri;
    private string $path;
    private string $queryString;
    private array $routeParams = [];

    public function __construct(
        ?array $get = null,
        ?array $post = null,
        ?array $server = null,
        ?array $files = null,
        ?array $cookies = null,
        ?string $rawBody = null
    ) {
        $this->getParams = $get ?? $_GET;
        $this->postParams = $post ?? $_POST;
        $this->serverParams = $server ?? $_SERVER;
        $this->files = $files ?? $_FILES;
        $this->cookies = $cookies ?? $_COOKIE;

        $this->method = strtoupper($this->serverParams['REQUEST_METHOD'] ?? 'GET');
        $this->uri = $this->serverParams['REQUEST_URI'] ?? '/';

        $pathParts = parse_url($this->uri);
        $this->path = $pathParts['path'] ?? '/';
        $this->queryString = $pathParts['query'] ?? '';

        $this->headers = $this->extractHeaders();

        if ($rawBody === null && $this->method !== 'GET') {
            $rawBody = file_get_contents('php://input');
        }

        if (!empty($rawBody) && $this->isJson()) {
            $this->jsonBody = json_decode($rawBody, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                // Handle JSON decode error, e.g., log it or set jsonBody to an error state
                // For now, it remains null if parsing fails after being initially identified as JSON.
                $this->jsonBody = null;
                 error_log('Request: Failed to parse JSON body. Error: ' . json_last_error_msg());
            }
        }
    }

    private function extractHeaders(): array
    {
        $headers = [];
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
        } else {
            foreach ($this->serverParams as $name => $value) {
                if (str_starts_with($name, 'HTTP_')) {
                    $headerName = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                    $headers[$headerName] = $value;
                } elseif (in_array($name, ['CONTENT_TYPE', 'CONTENT_LENGTH', 'CONTENT_MD5'], true)) {
                     $headerName = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', $name))));
                    $headers[$headerName] = $value;
                }
            }
        }
        return $headers;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function getQueryString(): string
    {
        return $this->queryString;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function isMethod(string $method): bool
    {
        return $this->method === strtoupper($method);
    }

    public function get(?string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->getParams;
        }
        return $this->getParams[$key] ?? $default;
    }

    public function post(?string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->postParams;
        }
        return $this->postParams[$key] ?? $default;
    }

    public function file(?string $key = null): ?array
    {
        if ($key === null) {
            return $this->files;
        }
        return $this->files[$key] ?? null;
    }

    public function hasFile(string $key): bool
    {
        return isset($this->files[$key]) && $this->files[$key]['error'] !== UPLOAD_ERR_NO_FILE;
    }

    public function cookie(?string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->cookies;
        }
        return $this->cookies[$key] ?? $default;
    }

    public function header(?string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->headers;
        }
        $key = strtolower($key);
        // Search case-insensitively
        foreach ($this->headers as $headerKey => $value) {
            if (strtolower($headerKey) === $key) {
                return $value;
            }
        }
        return $default;
    }

    public function server(?string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->serverParams;
        }
        return $this->serverParams[$key] ?? $default;
    }

    public function input(string $key, $default = null)
    {
        // Order of precedence: POST, JSON body, GET
        if (isset($this->postParams[$key])) {
            return $this->postParams[$key];
        }
        if ($this->jsonBody !== null && isset($this->jsonBody[$key])) {
            return $this->jsonBody[$key];
        }
        if (isset($this->getParams[$key])) {
            return $this->getParams[$key];
        }
        return $default;
    }

    public function all(): array
    {
        // Merges GET, POST, and JSON body parameters. POST takes precedence over JSON, which takes precedence over GET.
        $all = $this->getParams;
        if ($this->jsonBody !== null) {
            $all = array_merge($all, $this->jsonBody);
        }
        $all = array_merge($all, $this->postParams); // $_POST data takes highest precedence for form-like data
        return $all;
    }
    
    public function getJsonBody(): ?array
    {
        return $this->jsonBody;
    }

    public function ip(): ?string
    {
        // Standard headers for IP, can be extended for trusted proxies
        $headersToCheck = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];

        foreach ($headersToCheck as $header) {
            if (isset($this->serverParams[$header])) {
                // HTTP_X_FORWARDED_FOR can contain a list of IPs
                $ips = explode(',', $this->serverParams[$header]);
                $ip = trim($ips[0]); // Take the first IP in the list
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        return $this->serverParams['REMOTE_ADDR'] ?? null;
    }

    public function userAgent(): ?string
    {
        return $this->header('User-Agent');
    }

    public function isAjax(): bool
    {
        return strtolower($this->header('X-Requested-With', '')) === 'xmlhttprequest';
    }

    public function isJson(): bool
    {
        $contentType = $this->header('Content-Type', '');
        return stripos($contentType, 'application/json') !== false;
    }

    public function isSecure(): bool
    {
        return (isset($this->serverParams['HTTPS']) && strtolower($this->serverParams['HTTPS']) === 'on') ||
               (isset($this->serverParams['HTTP_X_FORWARDED_PROTO']) && strtolower($this->serverParams['HTTP_X_FORWARDED_PROTO']) === 'https');
    }

    public function getScheme(): string
    {
        return $this->isSecure() ? 'https' : 'http';
    }

    public function getHost(): string
    {
        return $this->serverParams['HTTP_HOST'] ?? $this->serverParams['SERVER_NAME'] ?? 'localhost';
    }

    public function getPort(): int
    {
        return (int) ($this->serverParams['SERVER_PORT'] ?? ($this->isSecure() ? 443 : 80));
    }

    public function getBaseUrl(): string
    {
        $scheme = $this->getScheme();
        $host = $this->getHost();
        $port = $this->getPort();

        $baseUrl = $scheme . '://' . $host;
        if (($scheme === 'http' && $port !== 80) || ($scheme === 'https' && $port !== 443)) {
            $baseUrl .= ':' . $port;
        }
        return $baseUrl;
    }

    /**
     * Get the full URL for the current request.
     * @return string
     */
    public function fullUrl(): string
    {
        return $this->getBaseUrl() . $this->uri;
    }

    /**
     * Get a bearer token from the Authorization header.
     * @return string|null The token, or null if not found or invalid.
     */
    public function bearerToken(): ?string
    {
        $authorizationHeader = $this->header('Authorization');
        if ($authorizationHeader && preg_match('/Bearer\s+(.*)$/i', $authorizationHeader, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Set route parameters (called by Router when matching routes)
     */
    public function setRouteParams(array $params): void
    {
        $this->routeParams = $params;
    }

    /**
     * Get a route parameter value
     */
    public function getRouteParam(string $key, $default = null)
    {
        return $this->routeParams[$key] ?? $default;
    }

    /**
     * Get all route parameters
     */
    public function getRouteParams(): array
    {
        return $this->routeParams;
    }

    /**
     * Alias for query parameters
     */
    public function query(?string $key = null, $default = null)
    {
        return $this->get($key, $default);
    }

    /**
     * Get request body as array (from JSON or form data)
     */
    public function getBody(): ?array
    {
        if ($this->jsonBody !== null) {
            return $this->jsonBody;
        }
        if (!empty($this->postParams)) {
            return $this->postParams;
        }
        return null;
    }
}