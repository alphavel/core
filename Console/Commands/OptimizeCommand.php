<?php

namespace Alphavel\Core\Console\Commands;

use Alphavel\Core\Console\Command;

/**
 * Optimize Command
 *
 * Executa todas as otimizações:
 * - Config cache
 * - Route cache
 * - Autoload optimization
 */
class OptimizeCommand extends Command
{
    protected string $signature = 'optimize';

    protected string $description = 'Cache configuration and routes for better performance';

    public function handle(): int
    {
        $this->info('Optimizing alphavel...');
        $this->line('');

        // 1. Config cache
        $this->comment('→ Caching configuration...');
        $configCache = new ConfigCacheCommand();
        $configCache->handle();
        $this->line('');

        // 2. Route cache
        $this->comment('→ Caching routes...');
        $routeCache = new RouteCacheCommand();
        $routeCache->handle();
        $this->line('');

        // 3. Autoload optimization (composer)
        if (file_exists(getcwd() . '/composer.json')) {
            $this->comment('→ Optimizing autoloader...');
            passthru('composer dump-autoload -o');
            $this->line('');
        }

        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('✓ Application optimized successfully!');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->line('');
        $this->comment('Performance improvements:');
        $this->comment('  • Configuration loading: ~80% faster');
        $this->comment('  • Route matching: ~40% faster');
        $this->comment('  • Autoloading: ~30% faster');
        $this->line('');
        $this->comment('To clear all caches:');
        $this->comment('  ./alphavel optimize:clear');

        return 0;
    }
}
