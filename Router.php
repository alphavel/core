<?php

namespace Alphavel\Core;

class Router
{
    private array $routes = [];

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

        return $route;
    }

    public function dispatch(string $uri, string $method): ?array
    {
        foreach ($this->routes as $route) {
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
}
