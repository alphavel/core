<?php

namespace Alphavel\Framework\Console\Commands;

use Alphavel\Framework\Console\Command;

/**
 * Serve Command - Start development server
 */
class ServeCommand extends Command
{
    protected string $signature = 'serve';

    protected string $description = 'Start the Swoole HTTP server';

    public function handle(): int
    {
        $host = $this->ask('Host', '0.0.0.0');
        $port = $this->ask('Port', '9999');

        $this->info('Starting alphavel server...');
        $this->comment("Server running on: http://{$host}:{$port}");
        $this->comment('Press Ctrl+C to stop');
        $this->line('');

        // Start Swoole server
        $serverFile = dirname(__DIR__, 3) . '/public/index.php';

        if (! file_exists($serverFile)) {
            $this->error("Server file not found: {$serverFile}");

            return 1;
        }

        // Set environment variables
        putenv("SERVER_HOST={$host}");
        putenv("SERVER_PORT={$port}");

        // Execute server
        passthru("php {$serverFile}");

        return 0;
    }
}
