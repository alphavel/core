<?php

namespace Alphavel\Core\Console\Commands;

use Alphavel\Core\Console\Command;

class MakeCommandCommand extends Command
{
    protected string $signature = 'make:command {name} {--force}';

    protected string $description = 'Create a new console command';

    public function handle(): int
    {
        $name = $this->argument('name');
        $force = $this->option('force');

        // Validate name
        if (! preg_match('/^[A-Z][a-zA-Z0-9]+Command$/', $name)) {
            $this->error('Command name must be PascalCase and end with "Command"');
            $this->comment('Example: SendEmailCommand, ProcessQueueCommand');

            return self::FAILURE;
        }

        // Determine path
        $basePath = getcwd(); // Working directory (project root)
        $dir = $basePath . '/app/Console/Commands';
        $path = $dir . '/' . $name . '.php';

        // Check if exists
        if (file_exists($path) && ! $force) {
            $this->error("Command already exists: {$path}");
            $this->comment('Use --force to overwrite');

            return self::FAILURE;
        }

        // Create directory if not exists
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Generate stub
        $stub = $this->getStub($name);
        file_put_contents($path, $stub);

        $this->success("Command created: {$path}");
        $this->comment('');
        $this->comment('Next steps:');
        $this->line('  1. Implement your logic in handle() method');
        $this->line('  2. Run: ./alphavel ' . $this->getCommandSignature($name));

        return self::SUCCESS;
    }

    private function getStub(string $name): string
    {
        $commandSignature = $this->getCommandSignature($name);

        return <<<PHP
<?php

namespace App\Console\Commands;

use Alphavel\Core\Console\Command;

class {$name} extends Command
{
    protected string \$signature = '{$commandSignature} {argument?} {--option}';

    protected string \$description = 'Command description';

    public function handle(): int
    {
        \$argument = \$this->argument('argument');
        \$option = \$this->option('option');

        \$this->info('Command executed!');

        if (\$argument) {
            \$this->line("Argument: {\$argument}");
        }

        if (\$option) {
            \$this->comment("Option: {\$option}");
        }

        // TODO: Implement your logic here

        \$this->success('Done!');

        return self::SUCCESS;
    }
}

PHP;
    }

    private function getCommandSignature(string $name): string
    {
        // SendEmailCommand -> send:email
        // ProcessQueueCommand -> process:queue
        $name = str_replace('Command', '', $name);

        // Split camelCase
        $parts = preg_split('/(?=[A-Z])/', $name, -1, PREG_SPLIT_NO_EMPTY);

        return strtolower(implode(':', $parts));
    }
}
