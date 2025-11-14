<?php

namespace Alphavel\Core;

use Psr\Container\ContainerInterface;
use Alphavel\Core\Exceptions\NotFoundException;
use Alphavel\Core\Exceptions\ContainerException;

class Container implements ContainerInterface
{
    private static ?Container $instance = null;

    private array $bindings = [];

    private array $instances = [];

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

    public function bind(string $abstract, callable $concrete): void
    {
        $this->bindings[$abstract] = [
            'concrete' => $concrete,
            'shared' => false,
        ];
    }

    public function singleton(string $abstract, callable $concrete): void
    {
        $this->bindings[$abstract] = [
            'concrete' => $concrete,
            'shared' => true,
        ];
    }

    public function instance(string $abstract, mixed $instance): void
    {
        $this->instances[$abstract] = $instance;
    }

    /**
     * PSR-11: Finds an entry of the container by its identifier and returns it.
     */
    public function get(string $id): mixed
    {
        if (! $this->has($id)) {
            throw new NotFoundException("Entry '{$id}' not found in container");
        }

        try {
            return $this->make($id);
        } catch (\Exception $e) {
            throw new ContainerException(
                "Error while retrieving entry '{$id}': " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    public function make(string $abstract): mixed
    {
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        if (! isset($this->bindings[$abstract])) {
            if (class_exists($abstract)) {
                return new $abstract();
            }

            throw new \Exception("Binding [{$abstract}] not found in container");
        }

        $binding = $this->bindings[$abstract];
        $concrete = $binding['concrete']();

        if ($binding['shared']) {
            $this->instances[$abstract] = $concrete;
        }

        return $concrete;
    }

    public function has(string $abstract): bool
    {
        return isset($this->instances[$abstract]) || isset($this->bindings[$abstract]);
    }
}
