<?php

namespace Alphavel\Core\Console\Commands;

use Alphavel\Core\Console\Command;

/**
 * Make Migration Command
 *
 * Gera arquivo de migration
 */
class MakeMigrationCommand extends Command
{
    protected string $signature = 'make:migration';

    protected string $description = 'Create a new migration file';

    public function handle(): int
    {
        $name = $this->ask('Migration name (e.g., create_users_table):');

        if (empty($name)) {
            $this->error('Migration name is required');

            return 1;
        }

        // Generate timestamp prefix
        $timestamp = date('Y_m_d_His');
        $filename = "{$timestamp}_{$name}.php";

        $path = getcwd() . "/database/migrations/{$filename}";

        if (file_exists($path)) {
            $this->error("Migration already exists: {$filename}");

            return 1;
        }

        // Extract table name from migration name
        $tableName = $this->extractTableName($name);

        $stub = $this->getStub($name, $tableName);

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        file_put_contents($path, $stub);

        $this->info('✓ Migration created successfully!');
        $this->comment("  Location: database/migrations/{$filename}");
        $this->line('');
        $this->comment('Run migration:');
        $this->comment('  ./alphavel migrate');

        return 0;
    }

    private function extractTableName(string $name): string
    {
        // Try to extract table name from common patterns
        if (preg_match('/create_(\w+)_table/', $name, $matches)) {
            return $matches[1];
        }

        if (preg_match('/add_\w+_to_(\w+)_table/', $name, $matches)) {
            return $matches[1];
        }

        // Default
        return 'table_name';
    }

    private function getStub(string $name, string $tableName): string
    {
        $className = str_replace('_', '', ucwords($name, '_'));

        return <<<PHP
<?php

/**
 * Migration: {$className}
 * 
 * Created: {$this->now()}
 */
class {$className}
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        \$sql = "
            CREATE TABLE IF NOT EXISTS {$tableName} (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";

        // Execute migration
        \$db = Loader::load('Database');
        \$db->execute(\$sql);

        echo "✓ Created table: {$tableName}\n";
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        \$sql = "DROP TABLE IF EXISTS {$tableName}";

        \$db = Loader::load('Database');
        \$db->execute(\$sql);

        echo "✓ Dropped table: {$tableName}\n";
    }
}
PHP;
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
