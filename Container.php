<?php

namespace Alphavel\Framework;

use Psr\Container\ContainerInterface;
use Alphavel\Framework\Exceptions\NotFoundException;
use Alphavel\Framework\Exceptions\ContainerException;

class Container implements ContainerInterface
{
    private static ?Container $instance = null;

    private array $bindings = [];

    private array $instances = [];

    /**
     * Reflection cache: stores constructor parameters for each class
     * Format: ['ClassName' => [['name' => 'param', 'class' => 'TypeHint'], ...]]
     */
    private static array $reflectionCache = [];

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
        // Check if already instantiated (singleton)
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        // Check if has manual binding
        if (isset($this->bindings[$abstract])) {
            $binding = $this->bindings[$abstract];
            $concrete = $binding['concrete']();

            if ($binding['shared']) {
                $this->instances[$abstract] = $concrete;
            }

            return $concrete;
        }

        // Autowiring: Try to auto-resolve the class
        if (class_exists($abstract)) {
            return $this->resolve($abstract);
        }

        throw new \Exception("Binding [{$abstract}] not found in container and cannot be auto-resolved");
    }

    /**
     * Resolve a class using autowiring (dependency injection)
     */
    private function resolve(string $class): mixed
    {
        // Use cached reflection if available
        if (!isset(self::$reflectionCache[$class])) {
            $reflector = new \ReflectionClass($class);
            $constructor = $reflector->getConstructor();

            if ($constructor === null) {
                // No constructor, cache empty array and instantiate
                self::$reflectionCache[$class] = [];
                return new $class();
            }

            // Cache constructor parameters
            $dependencies = [];
            foreach ($constructor->getParameters() as $param) {
                $type = $param->getType();
                
                if ($type === null || $type->isBuiltin()) {
                    // No type hint or primitive type (string, int, etc.)
                    if ($param->isDefaultValueAvailable()) {
                        $dependencies[] = [
                            'name' => $param->getName(),
                            'class' => null,
                            'default' => $param->getDefaultValue()
                        ];
                    } else {
                        throw new \Exception(
                            "Cannot auto-resolve parameter \${$param->getName()} in {$class} (no type hint or default value)"
                        );
                    }
                } else {
                    // Has type hint (class/interface)
                    $dependencies[] = [
                        'name' => $param->getName(),
                        'class' => $type->getName(),
                        'default' => null
                    ];
                }
            }

            self::$reflectionCache[$class] = $dependencies;
        }

        // Build instance using cached reflection data
        return $this->buildInstance($class);
    }

    /**
     * Build class instance using cached reflection data
     */
    private function buildInstance(string $class): mixed
    {
        $dependencies = self::$reflectionCache[$class];

        if (empty($dependencies)) {
            return new $class();
        }

        // Resolve dependencies recursively
        $resolvedDependencies = [];
        foreach ($dependencies as $dependency) {
            if ($dependency['class'] === null) {
                // Use default value
                $resolvedDependencies[] = $dependency['default'];
            } else {
                // Recursively resolve the dependency
                $resolvedDependencies[] = $this->make($dependency['class']);
            }
        }

        return new $class(...$resolvedDependencies);
    }

    public function has(string $abstract): bool
    {
        return isset($this->instances[$abstract]) || isset($this->bindings[$abstract]);
    }
}
