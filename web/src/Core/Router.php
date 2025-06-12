<?php

declare(strict_types=1);

namespace App\Core;

class Router
{
    protected array $routes = [];
    protected array $namedRoutes = [];
    protected string $basePath = ''; // For applications not running at the web root

    public const METHOD_GET = 'GET';
    public const METHOD_POST = 'POST';
    public const METHOD_PUT = 'PUT';
    public const METHOD_DELETE = 'DELETE';
    public const METHOD_PATCH = 'PATCH';
    public const METHOD_OPTIONS = 'OPTIONS';
    public const METHOD_HEAD = 'HEAD';


    public function setBasePath(string $basePath): void
    {
        $this->basePath = rtrim($basePath, '/');
    }

    public function addRoute(string $method, string $path, $handler, ?string $name = null): self
    {
        $method = strtoupper($method);
        $path = $this->basePath . '/' . trim($path, '/');
        $path = ($path === $this->basePath . '/') ? $this->basePath . '/' : rtrim($path, '/');
        if (empty($path) && !empty($this->basePath)) { // Ensure root path is just base path if path is '/'
            $path = $this->basePath;
        } elseif (empty($path)) {
            $path = '/';
        }


        $this->routes[$method][$path] = [
            'handler' => $handler,
            'name' => $name,
            'params' => [],
            'regex' => $this->compileRouteRegex($path)
        ];

        if ($name) {
            $this->namedRoutes[$name] = $path;
        }
        return $this;
    }

    private function compileRouteRegex(string $path): string
    {
        // Convert route placeholders like {id} to named regex groups (?<id>[^/]+)
        $regex = preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_-]*)\}/', function ($matches) {
            return '(?<' . $matches[1] . '>[^/]+)';
        }, $path);

        // Escape forward slashes
        $regex = str_replace('/', '\\/', $regex);

        // Ensure the regex matches the full path
        return '/^' . $regex . '$/i';
    }

    public function get(string $path, $handler, ?string $name = null): self
    {
        return $this->addRoute(self::METHOD_GET, $path, $handler, $name);
    }

    public function post(string $path, $handler, ?string $name = null): self
    {
        return $this->addRoute(self::METHOD_POST, $path, $handler, $name);
    }

    public function put(string $path, $handler, ?string $name = null): self
    {
        return $this->addRoute(self::METHOD_PUT, $path, $handler, $name);
    }

    public function delete(string $path, $handler, ?string $name = null): self
    {
        return $this->addRoute(self::METHOD_DELETE, $path, $handler, $name);
    }

    public function patch(string $path, $handler, ?string $name = null): self
    {
        return $this->addRoute(self::METHOD_PATCH, $path, $handler, $name);
    }

    public function options(string $path, $handler, ?string $name = null): self
    {
        return $this->addRoute(self::METHOD_OPTIONS, $path, $handler, $name);
    }

    public function head(string $path, $handler, ?string $name = null): self
    {
        return $this->addRoute(self::METHOD_HEAD, $path, $handler, $name);
    }

    public function dispatch(Request $request, Response $response): void
    {
        $requestMethod = $request->getMethod();
        $requestPath = rtrim($request->getPath(), '/');
        if (empty($requestPath)) {
            $requestPath = '/';
        }
        

        $matchedRoute = null;
        $allowedMethods = [];

        foreach ($this->routes as $method => $routesForMethod) {
            foreach ($routesForMethod as $path => $routeData) {
                if (preg_match($routeData['regex'], $requestPath, $matches)) {
                    if ($method === $requestMethod) {
                        $matchedRoute = $routeData;
                        // Extract named parameters
                        foreach ($matches as $key => $value) {
                            if (is_string($key)) {
                                $matchedRoute['params'][$key] = $value;
                            }
                        }
                        break 2; // Found method and path match
                    }
                    $allowedMethods[] = $method; // Found path match, but method different
                }
            }
        }

        if ($matchedRoute) {
            // Pass route parameters to the request object
            $request->setRouteParams($matchedRoute['params']);
            $this->callHandler($matchedRoute['handler'], $matchedRoute['params'], $request, $response);
        } elseif (!empty($allowedMethods)) {
            // Path matched, but method not allowed
            $response->setStatusCode(405); // Method Not Allowed
            $response->setHeader('Allow', implode(', ', array_unique($allowedMethods)));
            $response->errorJson('METHOD_NOT_ALLOWED', 'The requested method is not allowed for this URI.', null, 405)->send();
        } else {
            // No route matched - return JSON error for API routes, HTML for others
            if (strpos($requestPath, '/api/') === 0) {
                $response->errorJson('ROUTE_NOT_FOUND', 'The requested API endpoint could not be found.', ['path' => $requestPath, 'method' => $requestMethod], 404)->send();
            } else {
                $response->setStatusCode(404); // Not Found
                $response->setContent('<h1>404 Not Found</h1><p>The requested page could not be found.</p>');
                $response->send();
            }
        }
    }

    protected function callHandler($handler, array $params, Request $request, Response $response): void
    {
        if (is_callable($handler)) {
            // Prepend request and response to params for callable, then route params
            $args = array_merge([$request, $response], $params);
            call_user_func_array($handler, $args);
            // If the callable doesn't send the response, it might be an issue.
            // For simplicity, we assume callables handle their own response sending or return something.
            // If it returns content, the response object should be updated.
            // If it returns a Response object, we could use that.
            // For now, let's assume it echoes or uses $response->send()
             if (!$response->isHeadersSent() && !empty($response->getContent())) {
                 // If content was set but not sent (e.g., by json() but not followed by send()), send it.
                 // This is a bit of a heuristic for simple cases.
                 // $response->send();
             }

        } elseif (is_array($handler) && count($handler) === 2 && is_string($handler[0]) && is_string($handler[1])) {
            $controllerClass = $handler[0];
            $method = $handler[1];

            if (class_exists($controllerClass)) {
                $controller = new $controllerClass(); // Basic instantiation. DI container would be better.
                if (method_exists($controller, $method)) {
                    // Controller methods only take Request and Response objects
                    // Route parameters are accessible via $request->getRouteParam()
                    call_user_func_array([$controller, $method], [$request, $response]);

                    // Similar to callables, check if response needs sending.
                    // if (!$response->isHeadersSent() && !empty($response->getContent())) {
                    //     $response->send();
                    // }
                } else {
                    $this->handleRouterError($response, 500, "Method '{$method}' not found in controller '{$controllerClass}'.");
                }
            } else {
                $this->handleRouterError($response, 500, "Controller class '{$controllerClass}' not found.");
            }
        } else {
            $this->handleRouterError($response, 500, 'Invalid route handler specified.');
        }
    }

    private function handleRouterError(Response $response, int $statusCode, string $message): void
    {
        // Log the error
        error_log("Router Error: {$message}");

        if (!$response->isHeadersSent()) {
            $response->setStatusCode($statusCode);
            // In debug mode, show detailed error, otherwise generic
            $app = Application::getInstance(); // Assuming Application class has getInstance and getConfig
            if ($app && $app->getConfig('app.debug', false)) {
                $response->setContent("<h1>Router Error</h1><p>" . htmlspecialchars($message) . "</p>");
            } else {
                $response->setContent("<h1>Server Error</h1><p>An unexpected error occurred while processing your request.</p>");
            }
            $response->send();
        }
        // If headers already sent, we can't do much other than log
    }

    public function generateUrl(string $name, array $params = []): ?string
    {
        if (!isset($this->namedRoutes[$name])) {
            return null;
        }

        $path = $this->namedRoutes[$name];

        // Replace placeholders with provided parameters
        foreach ($params as $key => $value) {
            $path = str_replace('{' . $key . '}', (string)$value, $path);
        }

        // Check if any placeholders remain
        if (str_contains($path, '{')) {
            // Not all required parameters were provided
            error_log("Router: Not all parameters provided for named route '{$name}'. Path: {$path}");
            return null;
        }

        // For a full URL, you might want to prepend the base URL of the application
        // $baseUrl = Application::getInstance()->getConfig('app.url', '');
        // return rtrim($baseUrl, '/') . $path;

        return $path;
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }
}