<?php

namespace Alphavel\Core\Console\Commands;

use Alphavel\Core\Console\Command;

/**
 * Optimize Clear Command
 *
 * Remove todos os caches de otimização
 */
class OptimizeClearCommand extends Command
{
    protected string $signature = 'optimize:clear';

    protected string $description = 'Remove all cache files';

    public function handle(): int
    {
        $this->info('Clearing all caches...');
        $this->line('');

        // Config cache
        $configCache = new ConfigClearCommand();
        $configCache->handle();

        // Route cache
        $routeCache = new RouteClearCommand();
        $routeCache->handle();

        // Application cache
        $appCache = new CacheClearCommand();
        $appCache->handle();

        $this->line('');
        $this->info('✓ All caches cleared successfully!');

        return 0;
    }
}
