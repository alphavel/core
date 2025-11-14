<?php

namespace Alphavel\Core\Console\Commands;

use Alphavel\Core\Console\Command;

/**
 * Make Test Command
 *
 * Gera classe de teste PHPUnit
 */
class MakeTestCommand extends Command
{
    protected string $signature = 'make:test';

    protected string $description = 'Create a new test class';

    public function handle(): int
    {
        $name = $this->ask('Test name (e.g., UserTest):');

        if (empty($name)) {
            $this->error('Test name is required');

            return 1;
        }

        // Ensure name ends with "Test"
        if (! str_ends_with($name, 'Test')) {
            $name .= 'Test';
        }

        $type = $this->choice('Test type?', ['Feature', 'Unit'], 0);

        $path = getcwd() . "/tests/{$type}/{$name}.php";

        if (file_exists($path)) {
            $this->error("Test already exists: {$name}");

            return 1;
        }

        $stub = $this->getStub($name, $type);

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        file_put_contents($path, $stub);

        $this->info('✓ Test created successfully!');
        $this->comment("  Location: tests/{$type}/{$name}.php");
        $this->line('');
        $this->comment('Run test:');
        $this->comment("  vendor/bin/phpunit tests/{$type}/{$name}.php");

        return 0;
    }

    private function getStub(string $name, string $type): string
    {
        if ($type === 'Feature') {
            return $this->getFeatureStub($name);
        }

        return $this->getUnitStub($name);
    }

    private function getFeatureStub(string $name): string
    {
        return <<<PHP
<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

/**
 * {$name}
 * 
 * Feature test (integration test)
 */
class {$name} extends TestCase
{
    /**
     * Example test
     */
    public function test_example(): void
    {
        // Arrange: Setup test data
        // \$user = User::create(['name' => 'Test User']);

        // Act: Execute action
        // \$response = \$this->get('/api/users/' . \$user->id);

        // Assert: Verify results
        // \$this->assertEquals(200, \$response['status']);
        // \$this->assertEquals('Test User', \$response['data']['name']);

        \$this->assertTrue(true);
    }
}
PHP;
    }

    private function getUnitStub(string $name): string
    {
        return <<<PHP
<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * {$name}
 * 
 * Unit test (isolated test)
 */
class {$name} extends TestCase
{
    /**
     * Example test
     */
    public function test_example(): void
    {
        // Arrange: Setup test data
        \$value = 10;

        // Act: Execute function
        \$result = \$value * 2;

        // Assert: Verify result
        \$this->assertEquals(20, \$result);
    }
}
PHP;
    }
}
