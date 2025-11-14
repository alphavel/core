<?php

namespace Alphavel\Core\Console\Commands;

use Alphavel\Core\Console\Command;

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

        $routesFile = getcwd() . '/config/routes.php';
        $cachePath = getcwd() . '/storage/cache/routes.php';

        if (! file_exists($routesFile)) {
            $this->error('Routes file not found: config/routes.php');

            return 1;
        }

        // Simply copy the routes file to cache
        // In production, the application can check for cached routes first
        copy($routesFile, $cachePath);

        $this->info('✓ Routes cached successfully!');
        $this->comment('  Cached to: storage/cache/routes.php');
        $this->comment('  To clear cache, run: ./alphavel route:clear');
        $this->comment('');
        $this->comment('  Note: Update your bootstrap to load cached routes:');
        $this->comment("  if (file_exists(__DIR__ . '/storage/cache/routes.php')) {");
        $this->comment("      require __DIR__ . '/storage/cache/routes.php';");
        $this->comment('  } else {');
        $this->comment("      require __DIR__ . '/config/routes.php';");
        $this->comment('  }');

        return 0;
    }
}
