<?php

namespace Alphavel\Core\Console\Commands;

use Alphavel\Core\Console\Command;

class FacadeClearCommand extends Command
{
    protected string $signature = 'facade:clear';

    protected string $description = 'Remove the facade cache file';

    public function handle(): int
    {
        $cacheFile = getcwd() . '/storage/framework/facades.php';

        if (! file_exists($cacheFile)) {
            $this->info('No facade cache to clear');

            return self::SUCCESS;
        }

        unlink($cacheFile);

        $this->success('Facade cache cleared successfully');
        $this->comment('Facades will be regenerated on next request');

        return self::SUCCESS;
    }
}
