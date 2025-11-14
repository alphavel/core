<?php

namespace Alphavel\Core\Console\Commands;

use Alphavel\Core\Console\Command;

/**
 * List Routes Command
 */
class RouteListCommand extends Command
{
    protected string $signature = 'route:list';

    protected string $description = 'List all registered routes';

    public function handle(): int
    {
        $this->info('Loading routes...');

        // Load routes
        $routesFile = dirname(__DIR__, 3) . '/config/routes.php';

        if (! file_exists($routesFile)) {
            $this->error('Routes file not found.');

            return 1;
        }

        require_once dirname(__DIR__, 2) . '/Core/Loader.php';
        \Alphavel\Core\Loader::init();

        $router = \Alphavel\Core\Loader::load('Router');

        require $routesFile;

        $routes = $router->getRoutes();

        if (empty($routes)) {
            $this->warn('No routes registered.');

            return 0;
        }

        $rows = [];

        foreach ($routes as $method => $paths) {
            foreach ($paths as $path => $route) {
                $handler = $route['handler'];

                if (is_array($handler)) {
                    $handlerStr = $handler[0] . '@' . $handler[1];
                } elseif (is_string($handler)) {
                    $handlerStr = $handler;
                } else {
                    $handlerStr = 'Closure';
                }

                $middleware = isset($route['middleware']) && ! empty($route['middleware'])
                    ? implode(', ', array_map(fn ($m) => class_basename($m), $route['middleware']))
                    : '-';

                $rows[] = [
                    $method,
                    $path,
                    $handlerStr,
                    $middleware,
                ];
            }
        }

        $this->line('');
        $this->table(['Method', 'Path', 'Handler', 'Middleware'], $rows);
        $this->line('');
        $this->info('Total routes: ' . count($rows));

        return 0;
    }
}
