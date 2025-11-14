<?php

namespace Alphavel\Core\Console\Commands;

use Alphavel\Core\Console\Command;

// Cache: usar app("cache") em vez de facade;

/**
 * Cache Clear Command
 */
class CacheClearCommand extends Command
{
    protected string $signature = 'cache:clear';

    protected string $description = 'Clear the application cache';

    public function handle(): int
    {
        $this->info('Clearing application cache...');

        try {
            Cache::clear();
            $this->info('✓ Cache cleared successfully!');

            return 0;
        } catch (\Exception $e) {
            $this->error('Failed to clear cache: ' . $e->getMessage());

            return 1;
        }
    }
}
