<?php

namespace Alphavel\Core;

class Route
{
    private string $method;

    private string $path;

    private array|\Closure $handler;

    private array $middlewares = [];

    private ?string $pattern = null;

    public function __construct(string $method, string $path, string|array|\Closure $handler)
    {
        $this->method = $method;
        $this->path = $path;
        $this->handler = $this->parseHandler($handler);
        $this->pattern = $this->compilePattern($path);
    }

    private function parseHandler(string|array|\Closure $handler): array|\Closure
    {
        if ($handler instanceof \Closure) {
            return $handler;
        }

        if (is_array($handler)) {
            return $handler;
        }

        [$controller, $method] = explode('@', $handler);

        return [$controller, $method];
    }

    private function compilePattern(string $path): string
    {
        $pattern = preg_replace('/\{([^}]+)\}/', '(?P<$1>[^/]+)', $path);

        return '#^' . $pattern . '$#';
    }

    public function middleware(string|array $middleware): self
    {
        $this->middlewares = array_merge(
            $this->middlewares,
            is_array($middleware) ? $middleware : [$middleware]
        );

        return $this;
    }

    public function matches(string $uri, string $method): ?array
    {
        if ($this->method !== '*' && $this->method !== $method) {
            return null;
        }

        if (! preg_match($this->pattern, $uri, $matches)) {
            return null;
        }

        $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

        return [
            'handler' => $this->handler,
            'params' => $params,
            'middlewares' => $this->middlewares,
        ];
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }
}
