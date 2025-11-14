<?php

namespace Alphavel\Core\Console;

/**
 * Console Command Base (Artisan-like)
 *
 * Zero overhead: apenas para CLI, não afeta HTTP
 */
abstract class Command
{
    protected string $signature;

    protected string $description = '';

    private array $arguments = [];

    private array $options = [];

    public const SUCCESS = 0;

    public const FAILURE = 1;

    /**
     * Initialize command with argv
     */
    public function initialize(array $argv): void
    {
        $this->parseArguments($argv);
    }

    /**
     * Parse arguments and options from argv
     */
    private function parseArguments(array $argv): void
    {
        // Skip command name (first 2 elements)
        $args = array_slice($argv, 2);

        foreach ($args as $arg) {
            if (str_starts_with($arg, '--')) {
                // Long option: --option or --option=value
                $parts = explode('=', substr($arg, 2), 2);
                $this->options[$parts[0]] = $parts[1] ?? true;
            } elseif (str_starts_with($arg, '-')) {
                // Short option: -o
                $this->options[substr($arg, 1)] = true;
            } else {
                // Positional argument
                $this->arguments[] = $arg;
            }
        }
    }

    /**
     * Get argument by index or name
     */
    protected function argument(string|int $key, mixed $default = null): mixed
    {
        if (is_int($key)) {
            return $this->arguments[$key] ?? $default;
        }

        // Extract argument name from signature
        preg_match_all('/{(\w+)\??}/', $this->signature, $matches);
        $argNames = $matches[1] ?? [];
        $index = array_search($key, $argNames);

        if ($index === false) {
            return $default;
        }

        return $this->arguments[$index] ?? $default;
    }

    /**
     * Get option value
     */
    protected function option(string $key, mixed $default = null): mixed
    {
        return $this->options[$key] ?? $default;
    }

    /**
     * Execute command
     */
    abstract public function handle(): int;

    /**
     * Output info message (green)
     */
    protected function info(string $message): void
    {
        echo "\033[32m{$message}\033[0m\n";
    }

    /**
     * Output error message (red)
     */
    protected function error(string $message): void
    {
        echo "\033[31m{$message}\033[0m\n";
    }

    /**
     * Output warning message (yellow)
     */
    protected function warn(string $message): void
    {
        echo "\033[33m{$message}\033[0m\n";
    }

    /**
     * Output success message (bright green)
     */
    protected function success(string $message): void
    {
        echo "\033[1;32m✓ {$message}\033[0m\n";
    }

    /**
     * Output comment message (gray)
     */
    protected function comment(string $message): void
    {
        echo "\033[90m{$message}\033[0m\n";
    }

    /**
     * Output regular message
     */
    protected function line(string $message): void
    {
        echo "{$message}\n";
    }

    /**
     * Ask question
     */
    protected function ask(string $question, ?string $default = null): string
    {
        $prompt = $default ? "{$question} [{$default}]" : $question;
        echo "{$prompt}: ";

        $answer = trim(fgets(STDIN));

        return $answer ?: $default;
    }

    /**
     * Ask yes/no question
     */
    protected function confirm(string $question, bool $default = false): bool
    {
        $defaultText = $default ? 'yes' : 'no';
        echo "{$question} (yes/no) [{$defaultText}]: ";

        $answer = trim(strtolower(fgets(STDIN)));

        if ($answer === '') {
            return $default;
        }

        return in_array($answer, ['yes', 'y', '1', 'true']);
    }

    /**
     * Ask for choice from list
     */
    protected function choice(string $question, array $choices, ?string $default = null): string
    {
        echo "{$question}\n";

        foreach ($choices as $i => $choice) {
            echo "  [{$i}] {$choice}\n";
        }

        $defaultText = $default !== null ? " [{$default}]" : '';
        echo "Choice{$defaultText}: ";

        $input = trim(fgets(STDIN));

        if ($input === '' && $default !== null) {
            return $default;
        }

        $index = is_numeric($input) ? (int) $input : null;

        return $choices[$index] ?? ($choices[array_search($input, $choices)] ?? $choices[0]);
    }

    /**
     * Get signature
     */
    public function getSignature(): string
    {
        // Extract command name from signature (before first space or {)
        preg_match('/^([^\s{]+)/', $this->signature, $matches);

        return $matches[1] ?? $this->signature;
    }

    /**
     * Get description
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Progress bar
     */
    protected function progressBar(int $total, callable $callback): void
    {
        for ($i = 0; $i < $total; $i++) {
            $callback($i);

            $progress = ($i + 1) / $total;
            $bar = str_repeat('=', (int) ($progress * 50));
            $spaces = str_repeat(' ', 50 - strlen($bar));
            $percent = (int) ($progress * 100);

            echo "\r[{$bar}{$spaces}] {$percent}%";
        }

        echo "\n";
    }

    /**
     * Table output
     */
    protected function table(array $headers, array $rows): void
    {
        $columnWidths = [];

        // Calculate column widths
        foreach ($headers as $i => $header) {
            $columnWidths[$i] = strlen($header);
        }

        foreach ($rows as $row) {
            foreach ($row as $i => $cell) {
                $columnWidths[$i] = max($columnWidths[$i] ?? 0, strlen($cell));
            }
        }

        // Print header
        $this->printTableRow($headers, $columnWidths);
        $this->line(str_repeat('-', array_sum($columnWidths) + count($headers) * 3 + 1));

        // Print rows
        foreach ($rows as $row) {
            $this->printTableRow($row, $columnWidths);
        }
    }

    /**
     * Print table row
     */
    private function printTableRow(array $cells, array $widths): void
    {
        $line = '|';

        foreach ($cells as $i => $cell) {
            $width = $widths[$i] ?? 0;
            $line .= ' ' . str_pad($cell, $width) . ' |';
        }

        $this->line($line);
    }
}
