<?php

declare(strict_types=1);

namespace App\Core;

class Response
{
    protected string $content = '';
    protected int $statusCode = 200;
    protected array $headers = [];
    protected array $securityHeadersConfig = [];
    protected bool $headersSent = false;

    public function __construct(array $securityHeadersConfig = [])
    {
        $this->securityHeadersConfig = $securityHeadersConfig;
        // Set default content type if not overridden
        $this->setHeader('Content-Type', 'text/html; charset=UTF-8');
    }

    public function setStatusCode(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function setHeader(string $name, string $value, bool $replace = true): self
    {
        if ($replace) {
            $this->headers[strtolower($name)] = ['value' => $value, 'replace' => true];
        } else {
            // Store as an array to handle multiple headers of the same name if replace is false
            // For simplicity in this initial version, we might just append or handle specific headers.
            // For now, let's assume we store them and PHP's header() function handles multiple calls.
            if (isset($this->headers[strtolower($name)]) && is_array($this->headers[strtolower($name)]['value'])) {
                 $this->headers[strtolower($name)]['value'][] = $value;
            } elseif (isset($this->headers[strtolower($name)])) {
                 $this->headers[strtolower($name)] = [
                     'value' => [$this->headers[strtolower($name)]['value'], $value],
                     'replace' => false
                 ];
            } else {
                $this->headers[strtolower($name)] = ['value' => $value, 'replace' => false];
            }
        }
        return $this;
    }

    public function getHeader(string $name): ?string
    {
        $name = strtolower($name);
        if (isset($this->headers[$name])) {
            // If multiple values, return the first or a comma-separated string
            return is_array($this->headers[$name]['value']) ? implode(', ', $this->headers[$name]['value']) : $this->headers[$name]['value'];
        }
        return null;
    }

    public function removeHeader(string $name): self
    {
        unset($this->headers[strtolower($name)]);
        return $this;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function json(
        array $data = [],
        string $message = 'Operation completed successfully.',
        int $statusCode = 200,
        array $headers = []
    ): self {
        $this->setHeader('Content-Type', 'application/json; charset=UTF-8');
        $this->setStatusCode($statusCode);

        foreach ($headers as $name => $value) {
            $this->setHeader($name, $value);
        }

        $responseData = [
            'success' => true,
            'data' => $data,
            'message' => $message,
            'timestamp' => gmdate('c'), // ISO 8601 format
        ];

        $this->content = json_encode($responseData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if (json_last_error() !== JSON_ERROR_NONE) {
            // Fallback for JSON encoding error
            $this->errorJsonInternal('JSON_ENCODE_ERROR', 'Failed to encode JSON response.', json_last_error_msg());
        }
        return $this;
    }

    public function errorJson(
        string $errorCode,
        string $errorMessage,
        ?array $details = null,
        int $statusCode = 400,
        array $headers = []
    ): self {
        $this->setHeader('Content-Type', 'application/json; charset=UTF-8');
        $this->setStatusCode($statusCode);

        foreach ($headers as $name => $value) {
            $this->setHeader($name, $value);
        }

        $errorPayload = [
            'code' => $errorCode,
            'message' => $errorMessage,
        ];
        if ($details !== null) {
            $errorPayload['details'] = $details;
        }

        $responseData = [
            'success' => false,
            'error' => $errorPayload,
            'timestamp' => gmdate('c'),
        ];
        $this->content = json_encode($responseData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->errorJsonInternal('JSON_ENCODE_ERROR', 'Failed to encode error JSON response.', json_last_error_msg());
        }
        return $this;
    }

    private function errorJsonInternal(string $errorCode, string $errorMessage, string $details): void
    {
        $this->setStatusCode(500);
        $this->setHeader('Content-Type', 'application/json; charset=UTF-8');
        $this->content = json_encode([
            'success' => false,
            'error' => [
                'code' => $errorCode,
                'message' => $errorMessage,
                'details' => $details,
            ],
            'timestamp' => gmdate('c'),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }


    public function redirect(string $url, int $statusCode = 302): self
    {
        $this->setStatusCode($statusCode);
        $this->setHeader('Location', $url);
        // Clear content for redirects
        $this->content = '';
        return $this;
    }

    protected function applySecurityHeaders(): void
    {
        if (empty($this->securityHeadersConfig)) {
            // Attempt to load from Application instance if not provided in constructor
            // This is a fallback and ideally config should be injected.
            if (class_exists(Application::class) && method_exists(Application::class, 'getInstance')) {
                 $app = Application::getInstance();
                 $this->securityHeadersConfig = $app->getConfig('security.headers', []);
            }
        }

        foreach ($this->securityHeadersConfig as $name => $value) {
            if ($name === 'Content-Security-Policy' && is_array($value)) {
                if ($value['enabled'] ?? false) {
                    $cspHeaderValue = $this->buildCspHeader($value['directives'] ?? []);
                    if (!empty($cspHeaderValue)) {
                        $headerName = ($value['report_only'] ?? false) ? 'Content-Security-Policy-Report-Only' : 'Content-Security-Policy';
                        $this->setHeader($headerName, $cspHeaderValue);
                    }
                }
            } elseif (is_string($value) && !empty($value)) {
                $this->setHeader($name, $value);
            }
        }
    }

    protected function buildCspHeader(array $directives): string
    {
        $policyParts = [];
        foreach ($directives as $directive => $sources) {
            if (is_array($sources) && !empty($sources)) {
                $policyParts[] = $directive . ' ' . implode(' ', $sources);
            } elseif (is_string($sources) && $sources === 'true') { // For boolean directives like upgrade-insecure-requests
                $policyParts[] = $directive;
            } elseif (is_bool($sources) && $sources === true) {
                 $policyParts[] = $directive;
            }
        }
        return implode('; ', $policyParts);
    }


    public function send(): void
    {
        if ($this->headersSent) {
            // Log or throw an exception if headers are already sent
            error_log("Response::send() called after headers already sent.");
            return;
        }

        $this->applySecurityHeaders();

        if (!headers_sent()) {
            // Send status code
            http_response_code($this->statusCode);

            // Send headers
            foreach ($this->headers as $name => $headerData) {
                if (is_array($headerData['value'])) { // Handle multiple values for the same header name
                    foreach($headerData['value'] as $hVal) {
                        header("{$name}: {$hVal}", false); // false to allow multiple headers with same name
                    }
                } else {
                    header("{$name}: {$headerData['value']}", $headerData['replace']);
                }
            }
        }

        $this->headersSent = true;

        echo $this->content;

        // Terminate script execution if this is a final response.
        // Useful in some frameworks, but can be optional depending on application flow.
        // if (function_exists('fastcgi_finish_request')) {
        //     fastcgi_finish_request();
        // } elseif ('cli' !== PHP_SAPI) {
        //     // flush(); // Ensure all output has been sent
        // }
        // exit; // Be cautious with exit, it can make testing harder.
    }

    public function setCacheControl(array $directives): self
    {
        $parts = [];
        foreach ($directives as $key => $value) {
            if (is_bool($value) && $value === true) {
                $parts[] = $key;
            } elseif (is_string($value) || is_numeric($value)) {
                $parts[] = "{$key}={$value}";
            }
        }
        if (!empty($parts)) {
            $this->setHeader('Cache-Control', implode(', ', $parts));
        }
        return $this;
    }

    public function noCache(): self
    {
        $this->setCacheControl([
            'no-store' => true,
            'no-cache' => true,
            'must-revalidate' => true,
            'max-age' => 0
        ]);
        $this->setHeader('Pragma', 'no-cache'); // HTTP/1.0
        $this->setHeader('Expires', '0'); // Proxies
        return $this;
    }

    public function enableCors(string $origin = '*', string $methods = 'GET, POST, OPTIONS, PUT, DELETE', string $headers = 'Content-Type, Authorization, X-Requested-With'): self
    {
        $this->setHeader('Access-Control-Allow-Origin', $origin);
        $this->setHeader('Access-Control-Allow-Methods', $methods);
        $this->setHeader('Access-Control-Allow-Headers', $headers);
        if ($origin !== '*') {
            $this->setHeader('Access-Control-Allow-Credentials', 'true');
        }
        return $this;
    }

    public function isHeadersSent(): bool
    {
        return $this->headersSent || headers_sent();
    }
}