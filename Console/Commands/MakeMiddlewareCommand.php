<?php

namespace Alphavel\Core\Console\Commands;

use Alphavel\Core\Console\Command;

/**
 * Make Middleware Command
 *
 * Gera classe de Middleware
 */
class MakeMiddlewareCommand extends Command
{
    protected string $signature = 'make:middleware';

    protected string $description = 'Create a new middleware class';

    public function handle(): int
    {
        $name = $this->ask('Middleware name (e.g., AuthMiddleware):');

        if (empty($name)) {
            $this->error('Middleware name is required');

            return 1;
        }

        // Ensure name ends with "Middleware"
        if (! str_ends_with($name, 'Middleware')) {
            $name .= 'Middleware';
        }

        $path = getcwd() . "/app/Middlewares/{$name}.php";

        if (file_exists($path)) {
            $this->error("Middleware already exists: {$name}");

            return 1;
        }

        $stub = $this->getStub($name);

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        file_put_contents($path, $stub);

        $this->info('✓ Middleware created successfully!');
        $this->comment("  Location: app/Middlewares/{$name}.php");
        $this->line('');
        $this->comment('Usage in routes:');
        $this->comment("  \$router->get('/protected', 'Controller@method', [{$name}::class]);");

        return 0;
    }

    private function getStub(string $name): string
    {
        return <<<PHP
<?php

/**
 * {$name}
 * 
 * Middleware para processar requisições
 */
class {$name}
{
    /**
     * Handle incoming request
     * 
     * @param mixed \$request
     * @param callable \$next
     * @return mixed
     */
    public function handle(\$request, callable \$next)
    {
        // Execute code before request handling
        // Example: authentication, logging, validation
        
        // if (\$request->input('api_key') !== 'secret') {
        //     return ['error' => 'Unauthorized', 'status' => 401];
        // }

        // Continue to next middleware or controller
        \$response = \$next(\$request);

        // Execute code after request handling
        // Example: modify response, add headers
        
        return \$response;
    }
}
PHP;
    }
}
