<?php

namespace Alphavel\Core\Console\Commands;

use Alphavel\Core\Console\Command;

/**
 * Config Cache Command
 *
 * Compila todos os arquivos de config em um único arquivo
 * para melhorar performance (reduz I/O)
 */
class ConfigCacheCommand extends Command
{
    protected string $signature = 'config:cache';

    protected string $description = 'Create a cache file for faster configuration loading';

    public function handle(): int
    {
        $this->info('Caching configuration...');

        $configPath = getcwd() . '/config';
        $cachePath = getcwd() . '/storage/cache/config.php';

        if (! is_dir($configPath)) {
            $this->error('Config directory not found');

            return 1;
        }

        $configs = [];

        // Scan config directory
        $files = glob($configPath . '/*.php');

        foreach ($files as $file) {
            $key = basename($file, '.php');
            $configs[$key] = require $file;
            $this->line("  - Caching config/$key.php");
        }

        // Generate cache file
        $content = "<?php\n\nreturn " . var_export($configs, true) . ";\n";

        if (! is_dir(dirname($cachePath))) {
            mkdir(dirname($cachePath), 0755, true);
        }

        file_put_contents($cachePath, $content);

        $this->info('✓ Configuration cached successfully!');
        $this->comment('  Cached to: storage/cache/config.php');
        $this->comment('  To clear cache, run: ./alphavel config:clear');

        return 0;
    }
}
