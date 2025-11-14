<?php

namespace Alphavel\Core;

class Request
{
    private string $method;

    private string $uri;

    private array $query = [];

    private array $post = [];

    private array $headers = [];

    private array $server = [];

    private ?string $body = null;

    public function createFromSwoole($swooleRequest): self
    {
        $this->method = $swooleRequest->server['request_method'] ?? 'GET';
        $this->uri = $swooleRequest->server['request_uri'] ?? '/';
        $this->query = $swooleRequest->get ?? [];
        $this->post = $swooleRequest->post ?? [];
        $this->headers = $swooleRequest->header ?? [];
        $this->server = $swooleRequest->server ?? [];
        $this->body = $swooleRequest->rawContent() ?? null;

        return $this;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $this->query[$key] ?? $default;
    }

    public function all(): array
    {
        return array_merge($this->query, $this->post);
    }

    public function only(array $keys): array
    {
        return array_only($this->all(), $keys);
    }

    public function except(array $keys): array
    {
        return array_except($this->all(), $keys);
    }

    public function has(string $key): bool
    {
        return isset($this->post[$key]) || isset($this->query[$key]);
    }

    public function filled(string $key): bool
    {
        return $this->has($key) && ! blank($this->input($key));
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function header(string $key, mixed $default = null): mixed
    {
        $key = strtolower(str_replace('_', '-', $key));

        return $this->headers[$key] ?? $default;
    }

    public function bearerToken(): ?string
    {
        $header = $this->header('authorization', '');

        if (str_starts_with($header, 'Bearer ')) {
            return substr($header, 7);
        }

        return null;
    }

    public function json(?string $key = null, mixed $default = null): mixed
    {
        if ($this->body === null) {
            return $default;
        }

        $data = json_decode($this->body, true);

        if ($key === null) {
            return $data;
        }

        return data_get($data, $key, $default);
    }

    public function isJson(): bool
    {
        return str_contains($this->header('content-type', ''), 'application/json');
    }

    public function isPost(): bool
    {
        return $this->method === 'POST';
    }

    public function isGet(): bool
    {
        return $this->method === 'GET';
    }

    public function isPut(): bool
    {
        return $this->method === 'PUT';
    }

    public function isDelete(): bool
    {
        return $this->method === 'DELETE';
    }
}
