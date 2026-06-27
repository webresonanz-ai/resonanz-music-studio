<?php

namespace App\Core;

class Router
{
    private array $routes = [];
    private array $middlewares = [];
    private array $groupMiddlewares = [];

    public function get(string $path, string $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, string $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    public function group(array $options, callable $callback): void
    {
        $currentMiddlewares = $this->groupMiddlewares;
        if (isset($options['middleware'])) {
            $this->groupMiddlewares = array_merge($currentMiddlewares, (array) $options['middleware']);
        }
        
        $callback($this);
        
        $this->groupMiddlewares = $currentMiddlewares;
    }

    private function addRoute(string $method, string $path, string $handler): void
    {
        $middlewares = $this->groupMiddlewares;
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler,
            'middlewares' => $middlewares,
        ];
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $pattern = $this->convertToRegex($route['path']);
            if (preg_match($pattern, $uri, $matches)) {
                array_shift($matches);
                $this->runMiddlewares($route['middlewares']);
                $this->callHandler($route['handler'], $matches);
                return;
            }
        }

        $this->sendResponse(['error' => 'Not Found'], 404);
    }

    private function convertToRegex(string $path): string
    {
        $path = str_replace('/', '\/', $path);
        $path = preg_replace('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', '([^/]+)', $path);
        return '/^' . $path . '$/';
    }

    private function runMiddlewares(array $middlewares): void
    {
        foreach ($middlewares as $middleware) {
            $instance = new $middleware();
            $instance->handle();
        }
    }

    private function callHandler(string $handler, array $params = []): void
    {
        [$class, $method] = explode('@', $handler);
        
        if (!class_exists($class)) {
            $this->sendResponse(['error' => 'Controller not found'], 500);
            return;
        }

        $controller = new $class();
        
        if (!method_exists($controller, $method)) {
            $this->sendResponse(['error' => 'Method not found'], 500);
            return;
        }

        call_user_func_array([$controller, $method], $params);
    }

    protected function sendResponse(array $data, int $status = 200): void
    {
        http_response_code($status);
        echo json_encode($data);
    }
}