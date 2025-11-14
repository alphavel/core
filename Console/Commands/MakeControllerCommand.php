<?php

namespace Alphavel\Core\Console\Commands;

use Alphavel\Core\Console\Command;

/**
 * Make Controller Command
 */
class MakeControllerCommand extends Command
{
    protected string $signature = 'make:controller';

    protected string $description = 'Create a new controller class';

    public function handle(): int
    {
        $name = $this->ask('Controller name');

        if (! $name) {
            $this->error('Controller name is required.');

            return 1;
        }

        // Add Controller suffix if not present
        if (! str_ends_with($name, 'Controller')) {
            $name .= 'Controller';
        }

        $type = $this->choice(
            'Controller type',
            ['API (JSON responses)', 'Web (Views)', 'Resource (CRUD)'],
            'API (JSON responses)'
        );

        $stub = match ($type) {
            'Resource (CRUD)' => $this->getResourceStub($name),
            'Web (Views)' => $this->getWebStub($name),
            default => $this->getApiStub($name)
        };

        $path = dirname(__DIR__, 2) . "/Controllers/{$name}.php";

        if (file_exists($path)) {
            if (! $this->confirm('Controller already exists. Overwrite?')) {
                $this->warn('Operation cancelled.');

                return 0;
            }
        }

        file_put_contents($path, $stub);
        $this->info("Controller created successfully: {$name}");
        $this->comment("Location: app/Controllers/{$name}.php");

        return 0;
    }

    private function getApiStub(string $name): string
    {
        return <<<PHP
<?php

use Alphavel\Core\Request;
use Alphavel\Core\Response;

/**
 * {$name}
 */
class {$name} extends Controller
{
    /**
     * Display a listing of the resource
     */
    public function index(Request \$request): Response
    {
        return Response::success([
            'message' => 'List from {$name}'
        ]);
    }

    /**
     * Display the specified resource
     */
    public function show(Request \$request, int \$id): Response
    {
        return Response::success([
            'id' => \$id,
            'message' => 'Show from {$name}'
        ]);
    }

    /**
     * Store a newly created resource
     */
    public function store(Request \$request): Response
    {
        return Response::success([
            'message' => 'Created successfully'
        ], 201);
    }

    /**
     * Update the specified resource
     */
    public function update(Request \$request, int \$id): Response
    {
        return Response::success([
            'id' => \$id,
            'message' => 'Updated successfully'
        ]);
    }

    /**
     * Remove the specified resource
     */
    public function destroy(Request \$request, int \$id): Response
    {
        return Response::success([
            'message' => 'Deleted successfully'
        ]);
    }
}

PHP;
    }

    private function getWebStub(string $name): string
    {
        return <<<PHP
<?php

use Alphavel\Core\Request;

/**
 * {$name}
 */
class {$name} extends Controller
{
    /**
     * Display a listing of the resource
     */
    public function index(Request \$request)
    {
        return view('index', [
            'title' => 'Welcome'
        ]);
    }

    /**
     * Display the specified resource
     */
    public function show(Request \$request, int \$id)
    {
        return view('show', [
            'id' => \$id
        ]);
    }
}

PHP;
    }

    private function getResourceStub(string $name): string
    {
        $model = str_replace('Controller', '', $name);

        return <<<PHP
<?php

use Alphavel\Core\Request;
use Alphavel\Core\Response;

/**
 * {$name} - RESTful Resource Controller
 */
class {$name} extends Controller
{
    /**
     * Display a listing of the resource
     * GET /resource
     */
    public function index(Request \$request): Response
    {
        \$items = {$model}::all();
        
        return Response::success(\$items);
    }

    /**
     * Display the specified resource
     * GET /resource/{id}
     */
    public function show(Request \$request, int \$id): Response
    {
        \$item = {$model}::find(\$id);
        
        if (!\$item) {
            return Response::error('Not found', 404);
        }
        
        return Response::success(\$item);
    }

    /**
     * Store a newly created resource
     * POST /resource
     */
    public function store(Request \$request): Response
    {
        \$data = \$request->only(['name', 'email']); // Customize fields
        
        // Validate
        \$errors = \$request->validate(\$data, [
            'name' => 'required|min:3',
            'email' => 'required|email'
        ]);
        
        if (\$errors) {
            return Response::error('Validation failed', 422, \$errors);
        }
        
        \$item = {$model}::create(\$data);
        
        return Response::success(\$item, 201);
    }

    /**
     * Update the specified resource
     * PUT /resource/{id}
     */
    public function update(Request \$request, int \$id): Response
    {
        \$item = {$model}::find(\$id);
        
        if (!\$item) {
            return Response::error('Not found', 404);
        }
        
        \$data = \$request->only(['name', 'email']); // Customize fields
        
        foreach (\$data as \$key => \$value) {
            \$item->\$key = \$value;
        }
        
        \$item->save();
        
        return Response::success(\$item);
    }

    /**
     * Remove the specified resource
     * DELETE /resource/{id}
     */
    public function destroy(Request \$request, int \$id): Response
    {
        \$item = {$model}::find(\$id);
        
        if (!\$item) {
            return Response::error('Not found', 404);
        }
        
        \$item->delete();
        
        return Response::success(['message' => 'Deleted successfully']);
    }
}

PHP;
    }
}
