<?php

namespace Alphavel\Core;

class Config
{
    private static ?Config $instance = null;

    private array $items = [];

    private function __construct()
    {
        //
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function load(string $path): void
    {
        if (! file_exists($path)) {
            return;
        }

        $config = require $path;

        if (is_array($config)) {
            $this->items = array_merge($this->items, $config);
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return data_get($this->items, $key, $default);
    }

    public function set(string $key, mixed $value): void
    {
        $keys = explode('.', $key);
        $items = &$this->items;

        while (count($keys) > 1) {
            $key = array_shift($keys);

            if (! isset($items[$key]) || ! is_array($items[$key])) {
                $items[$key] = [];
            }

            $items = &$items[$key];
        }

        $items[array_shift($keys)] = $value;
    }

    public function has(string $key): bool
    {
        return data_get($this->items, $key) !== null;
    }

    public function all(): array
    {
        return $this->items;
    }
}
