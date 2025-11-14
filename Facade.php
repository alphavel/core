<?php

namespace Alphavel\Core;

use RuntimeException;

/**
 * Base Facade Class
 *
 * Provides static interface to container-resolved instances
 * Zero overhead: resolved once per request via singleton container
 */
abstract class Facade
{
    /**
     * Get the registered name of the component
     */
    protected static function getFacadeAccessor(): string
    {
        throw new RuntimeException('Facade does not implement getFacadeAccessor method');
    }

    /**
     * Get the root object behind the facade
     */
    protected static function getFacadeRoot(): mixed
    {
        return Application::getInstance()->make(static::getFacadeAccessor());
    }

    /**
     * Handle dynamic static calls to the facade
     */
    public static function __callStatic(string $method, array $args): mixed
    {
        $instance = static::getFacadeRoot();

        if (! $instance) {
            throw new RuntimeException('A facade root has not been set');
        }

        return $instance->$method(...$args);
    }
}
