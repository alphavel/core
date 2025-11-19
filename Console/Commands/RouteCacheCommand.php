<?php

namespace Alphavel\Framework\Console\Commands;

use Alphavel\Framework\Console\Command;

/**
 * Route Cache Command
 *
 * Compila rotas em um único arquivo para melhor performance
 */
class RouteCacheCommand extends Command
{
    protected string $signature = 'route:cache';

    protected string $description = 'Create a route cache file for faster route registration';

    public function handle(): int
    {
        $this->info('Caching routes...');

        $app = \Alphavel\Framework\Application::getInstance();
        $router = $app->make('router');

        // Ensure routes are loaded
        if (empty($router->getRoutes())) {
            $routesFile = getcwd() . '/routes/api.php';
            if (file_exists($routesFile)) {
                require $routesFile;
            }
        }

        $staticRoutes = $router->getStaticRoutes();
        $dynamicRoutes = $router->getDynamicRoutes();

        // Check for closures
        $this->checkForClosures($staticRoutes);
        $this->checkForClosures($dynamicRoutes);

        $cache = [
            'static' => $staticRoutes,
            'dynamic' => $dynamicRoutes,
        ];

        $cachePath = getcwd() . '/storage/cache/routes.php';
        
        // Use serialization with base64 encoding to handle objects safely
        $content = "<?php\n\nreturn unserialize(base64_decode('" . base64_encode(serialize($cache)) . "'));\n";

        file_put_contents($cachePath, $content);

        $this->info('✓ Routes cached successfully!');
        $this->comment('  Cached to: storage/cache/routes.php');
        
        return 0;
    }

    private function checkForClosures(array $routes): void
    {
        array_walk_recursive($routes, function ($item) {
            if ($item instanceof \Alphavel\Framework\Route) {
                $handler = $this->getPrivateProperty($item, 'handler');
                if ($handler instanceof \Closure) {
                    $this->error('Unable to cache routes with Closures.');
                    $this->error('Route: ' . $this->getPrivateProperty($item, 'method') . ' ' . $this->getPrivateProperty($item, 'path'));
                    throw new \RuntimeException('Route caching failed due to Closure handler.');
                }
            }
        });
    }

    private function getPrivateProperty(object $object, string $property): mixed
    {
        $reflection = new \ReflectionClass($object);
        $prop = $reflection->getProperty($property);
        $prop->setAccessible(true);
        return $prop->getValue($object);
    }
}
