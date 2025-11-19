<?php

namespace Alphavel\Framework;

class Router
{
    private array $routes = [];
    private array $staticRoutes = [];
    private array $dynamicRoutes = [];

    public function get(string $path, string|array|\Closure $handler): Route
    {
        return $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, string|array|\Closure $handler): Route
    {
        return $this->addRoute('POST', $path, $handler);
    }

    public function put(string $path, string|array|\Closure $handler): Route
    {
        return $this->addRoute('PUT', $path, $handler);
    }

    public function delete(string $path, string|array|\Closure $handler): Route
    {
        return $this->addRoute('DELETE', $path, $handler);
    }

    public function patch(string $path, string|array|\Closure $handler): Route
    {
        return $this->addRoute('PATCH', $path, $handler);
    }

    public function any(string $path, string|array|\Closure $handler): Route
    {
        return $this->addRoute('*', $path, $handler);
    }

    private function addRoute(string $method, string $path, string|array|\Closure $handler): Route
    {
        $route = new Route($method, $path, $handler);
        $this->routes[] = $route;

        // Optimization: Separate static and dynamic routes
        if (strpos($path, '{') === false) {
            $this->staticRoutes[$method][$path] = $route;
        } else {
            $this->dynamicRoutes[$method][] = $route;
        }

        return $route;
    }

    public function dispatch(string $uri, string $method): ?array
    {
        // 1. Fast lookup for static routes (O(1))
        if (isset($this->staticRoutes[$method][$uri])) {
            return $this->staticRoutes[$method][$uri]->matches($uri, $method);
        }

        // 2. Regex lookup for dynamic routes
        foreach ($this->dynamicRoutes[$method] ?? [] as $route) {
            if ($match = $route->matches($uri, $method)) {
                return $match;
            }
        }

        return null;
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }

    public function getStaticRoutes(): array
    {
        return $this->staticRoutes;
    }

    public function getDynamicRoutes(): array
    {
        return $this->dynamicRoutes;
    }

    public function loadCachedRoutes(array $routes): void
    {
        $this->staticRoutes = $routes['static'] ?? [];
        $this->dynamicRoutes = $routes['dynamic'] ?? [];
        $this->routes = []; // Clear raw routes to save memory
    }
}
