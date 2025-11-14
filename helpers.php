<?php

if (! function_exists('app')) {
    function app(?string $abstract = null): mixed
    {
        $app = \Alphavel\Core\Application::getInstance();

        return $abstract ? $app->make($abstract) : $app;
    }
}

if (! function_exists('config')) {
    function config(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return app('config');
        }

        return app('config')->get($key, $default);
    }
}

if (! function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        if ($value === false) {
            return $default;
        }

        return $value;
    }
}

if (! function_exists('data_get')) {
    function data_get(mixed $target, string $key, mixed $default = null): mixed
    {
        if (is_array($target)) {
            return array_get($target, $key, $default);
        }

        if (is_object($target)) {
            foreach (explode('.', $key) as $segment) {
                if (! is_object($target) || ! isset($target->$segment)) {
                    return $default;
                }

                $target = $target->$segment;
            }

            return $target;
        }

        return $default;
    }
}

if (! function_exists('array_get')) {
    function array_get(array $array, string $key, mixed $default = null): mixed
    {
        if (isset($array[$key])) {
            return $array[$key];
        }

        foreach (explode('.', $key) as $segment) {
            if (! is_array($array) || ! array_key_exists($segment, $array)) {
                return $default;
            }

            $array = $array[$segment];
        }

        return $array;
    }
}

if (! function_exists('array_only')) {
    function array_only(array $array, array $keys): array
    {
        return array_intersect_key($array, array_flip($keys));
    }
}

if (! function_exists('array_except')) {
    function array_except(array $array, array $keys): array
    {
        return array_diff_key($array, array_flip($keys));
    }
}

if (! function_exists('blank')) {
    function blank(mixed $value): bool
    {
        if (is_null($value)) {
            return true;
        }

        if (is_string($value)) {
            return trim($value) === '';
        }

        if (is_numeric($value) || is_bool($value)) {
            return false;
        }

        if ($value instanceof Countable) {
            return count($value) === 0;
        }

        return empty($value);
    }
}

if (! function_exists('filled')) {
    function filled(mixed $value): bool
    {
        return ! blank($value);
    }
}

if (! function_exists('str_contains')) {
    function str_contains(string $haystack, string $needle): bool
    {
        return strpos($haystack, $needle) !== false;
    }
}

if (! function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool
    {
        return strpos($haystack, $needle) === 0;
    }
}

if (! function_exists('now')) {
    function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}

if (! function_exists('today')) {
    function today(): string
    {
        return date('Y-m-d');
    }
}
