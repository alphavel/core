<?php

namespace Alphavel\Core;

class Response
{
    private int $statusCode = 200;

    private array $headers = [];

    private string $content = '';

    public static function make(): self
    {
        return new self();
    }

    public static function success(mixed $data = null, int $status = 200): self
    {
        return self::make()->json([
            'status' => 'success',
            'data' => $data,
        ], $status);
    }

    public static function error(string $message, int $status = 400, mixed $errors = null): self
    {
        $data = [
            'status' => 'error',
            'message' => $message,
        ];

        if ($errors !== null) {
            $data['errors'] = $errors;
        }

        return self::make()->json($data, $status);
    }

    public function json(mixed $data, int $status = 200): self
    {
        $this->header('Content-Type', 'application/json');
        $this->content = json_encode($data);
        $this->statusCode = $status;

        return $this;
    }

    public function content(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function status(int $code): self
    {
        $this->statusCode = $code;

        return $this;
    }

    public function header(string $key, string $value): self
    {
        $this->headers[$key] = $value;

        return $this;
    }

    public function redirect(string $url, int $status = 302): self
    {
        $this->header('Location', $url);
        $this->statusCode = $status;

        return $this;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getContent(): string
    {
        return $this->content;
    }
}
