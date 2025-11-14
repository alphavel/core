<?php

namespace Alphavel\Core\Console;

/**
 * Console Application (Artisan-like)
 */
class Console
{
    private array $commands = [];

    private string $name = 'alphavel';

    private string $version = '1.0.0';

    /**
     * Register command
     */
    public function register(string $signature, string $class): void
    {
        $this->commands[$signature] = $class;
    }

    /**
     * Auto-discover commands from a directory
     */
    public function autodiscover(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        $files = glob($directory . '/*Command.php');

        foreach ($files as $file) {
            $className = $this->getClassNameFromFile($file);

            if (! $className || ! class_exists($className)) {
                continue;
            }

            try {
                $instance = new $className();

                if (method_exists($instance, 'getSignature')) {
                    $signature = $instance->getSignature();
                    $this->register($signature, $className);
                }
            } catch (\Throwable $e) {
                // Skip invalid commands
            }
        }
    }

    /**
     * Get class name from file path
     */
    private function getClassNameFromFile(string $file): ?string
    {
        $content = file_get_contents($file);

        // Extract namespace
        if (! preg_match('/namespace\s+([^;]+);/', $content, $namespaceMatches)) {
            return null;
        }

        // Extract class name
        if (! preg_match('/class\s+(\w+)/', $content, $classMatches)) {
            return null;
        }

        return $namespaceMatches[1] . '\\' . $classMatches[1];
    }

    /**
     * Run console
     */
    public function run(array $argv): int
    {
        $commandName = $argv[1] ?? 'list';

        // List commands
        if ($commandName === 'list' || $commandName === '--list') {
            $this->listCommands();

            return 0;
        }

        // Version
        if ($commandName === '--version' || $commandName === '-v') {
            echo "{$this->name} version {$this->version}\n";

            return 0;
        }

        // Help
        if ($commandName === '--help' || $commandName === '-h') {
            $this->showHelp();

            return 0;
        }

        // Execute command
        if (! isset($this->commands[$commandName])) {
            echo "\033[31mCommand '{$commandName}' not found.\033[0m\n\n";
            echo "Run '{$this->name} list' to see available commands.\n";

            return 1;
        }

        try {
            $commandClass = $this->commands[$commandName];
            $command = new $commandClass();

            // Initialize with argv
            if (method_exists($command, 'initialize')) {
                $command->initialize($argv);
            }

            return $command->handle();
        } catch (\Throwable $e) {
            echo "\033[31mError: {$e->getMessage()}\033[0m\n";
            echo "\nStack trace:\n";
            echo $e->getTraceAsString();

            return 1;
        }
    }

    /**
     * List all commands
     */
    private function listCommands(): void
    {
        echo "\033[32m{$this->name}\033[0m version \033[33m{$this->version}\033[0m\n\n";
        echo "Usage:\n";
        echo "  command [options] [arguments]\n\n";
        echo "Available commands:\n";

        $maxLength = 0;
        foreach (array_keys($this->commands) as $signature) {
            $maxLength = max($maxLength, strlen($signature));
        }

        foreach ($this->commands as $signature => $class) {
            $instance = new $class();
            $description = $instance->getDescription();

            echo "  \033[32m" . str_pad($signature, $maxLength + 2) . "\033[0m";
            echo $description . "\n";
        }

        echo "\n";
    }

    /**
     * Show help
     */
    private function showHelp(): void
    {
        echo "\033[32m{$this->name}\033[0m - Fast PHP Framework\n\n";
        echo "Usage:\n";
        echo "  {$this->name} <command> [options] [arguments]\n\n";
        echo "Options:\n";
        echo "  -h, --help     Display help message\n";
        echo "  -v, --version  Display version\n";
        echo "  list           List all commands\n\n";
    }

    /**
     * Set name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Set version
     */
    public function setVersion(string $version): void
    {
        $this->version = $version;
    }
}
