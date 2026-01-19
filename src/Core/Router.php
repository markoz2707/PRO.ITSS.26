<?php

namespace ITSS\Core;

class Router
{
    private array $routes = [];
    private array $middlewares = [];

    public function get(string $path, callable $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, callable $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    public function put(string $path, callable $handler): void
    {
        $this->addRoute('PUT', $path, $handler);
    }

    public function delete(string $path, callable $handler): void
    {
        $this->addRoute('DELETE', $path, $handler);
    }

    private function addRoute(string $method, string $path, callable $handler): void
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler
        ];
    }

    public function middleware(callable $middleware): void
    {
        $this->middlewares[] = $middleware;
    }

    public function dispatch(string $method, string $uri): void
    {
        $uri = parse_url($uri, PHP_URL_PATH);
        $uri = rtrim($uri, '/') ?: '/';

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $pattern = $this->convertPathToRegex($route['path']);
            if (preg_match($pattern, $uri, $matches)) {
                array_shift($matches);

                $request = new Request();
                $response = new Response();

                try {
                    foreach ($this->middlewares as $middleware) {
                        $result = $middleware($request, $response);
                        if ($result === false) {
                            return;
                        }
                    }

                    call_user_func_array($route['handler'], array_merge([$request, $response], $matches));
                } catch (\Exception $e) {
                    Logger::error('Route handler error: ' . $e->getMessage(), [
                        'route' => $route['path'],
                        'method' => $method,
                        'trace' => $e->getTraceAsString()
                    ]);
                    $response->status(500)->json(['error' => 'Internal server error']);
                }
                return;
            }
        }

        $response = new Response();
        $response->status(404)->json(['error' => 'Not found']);
    }

    private function convertPathToRegex(string $path): string
    {
        $path = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([^/]+)', $path);
        return '#^' . $path . '$#';
    }
}
