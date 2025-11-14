<?php

namespace Alphavel\Core\Console\Commands;

use Alphavel\Core\Console\Command;

/**
 * Config Clear Command
 *
 * Remove o cache de configuração
 */
class ConfigClearCommand extends Command
{
    protected string $signature = 'config:clear';

    protected string $description = 'Remove the configuration cache file';

    public function handle(): int
    {
        $cachePath = getcwd() . '/storage/cache/config.php';

        if (! file_exists($cachePath)) {
            $this->comment('Configuration cache does not exist.');

            return 0;
        }

        unlink($cachePath);
        $this->info('✓ Configuration cache cleared!');

        return 0;
    }
}
