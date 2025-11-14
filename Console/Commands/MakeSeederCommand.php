<?php

namespace Alphavel\Core\Console\Commands;

use Alphavel\Core\Console\Command;

/**
 * Make Seeder Command
 *
 * Gera classe de Database Seeder
 */
class MakeSeederCommand extends Command
{
    protected string $signature = 'make:seeder';

    protected string $description = 'Create a new seeder class';

    public function handle(): int
    {
        $name = $this->ask('Seeder name (e.g., UserSeeder):');

        if (empty($name)) {
            $this->error('Seeder name is required');

            return 1;
        }

        // Ensure name ends with "Seeder"
        if (! str_ends_with($name, 'Seeder')) {
            $name .= 'Seeder';
        }

        $path = getcwd() . "/database/seeders/{$name}.php";

        if (file_exists($path)) {
            $this->error("Seeder already exists: {$name}");

            return 1;
        }

        $stub = $this->getStub($name);

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        file_put_contents($path, $stub);

        $this->info('✓ Seeder created successfully!');
        $this->comment("  Location: database/seeders/{$name}.php");
        $this->line('');
        $this->comment('Run seeder:');
        $this->comment("  ./alphavel db:seed --class={$name}");

        return 0;
    }

    private function getStub(string $name): string
    {
        return <<<PHP
<?php

/**
 * {$name}
 * 
 * Database seeder para popular dados
 */
class {$name}
{
    /**
     * Run the database seeds
     */
    public function run(): void
    {
        // Example: Create sample users
        // for (\$i = 1; \$i <= 10; \$i++) {
        //     User::create([
        //         'name' => "User {\$i}",
        //         'email' => "user{\$i}@example.com",
        //         'password' => password_hash('password', PASSWORD_DEFAULT),
        //         'status' => 'active',
        //     ]);
        // }

        echo "Seeded: {$name}\n";
    }
}
PHP;
    }
}
