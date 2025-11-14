<?php

namespace Alphavel\Core\Console\Commands;

use Alphavel\Core\Console\Command;

/**
 * Make Request Command
 *
 * Gera classe de Form Request para validação
 */
class MakeRequestCommand extends Command
{
    protected string $signature = 'make:request';

    protected string $description = 'Create a new form request class';

    public function handle(): int
    {
        $name = $this->ask('Request name (e.g., StoreUserRequest):');

        if (empty($name)) {
            $this->error('Request name is required');

            return 1;
        }

        // Ensure name ends with "Request"
        if (! str_ends_with($name, 'Request')) {
            $name .= 'Request';
        }

        $path = getcwd() . "/app/Requests/{$name}.php";

        if (file_exists($path)) {
            $this->error("Request already exists: {$name}");

            return 1;
        }

        $stub = $this->getStub($name);

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        file_put_contents($path, $stub);

        $this->info('✓ Form Request created successfully!');
        $this->comment("  Location: app/Requests/{$name}.php");
        $this->line('');
        $this->comment('Usage in controller:');
        $this->comment('  public function store(Request $request) {');
        $this->comment("      \$validator = new {$name}();");
        $this->comment('      $errors = $validator->validate($request);');
        $this->comment("      if (\$errors) return Response::error('Validation failed', 422, \$errors);");
        $this->comment('  }');

        return 0;
    }

    private function getStub(string $name): string
    {
        $stub = <<<'PHP'
<?php

/**
 * {NAME}
 * 
 * Form Request para validação de dados
 */
class {NAME}
{
    /**
     * Get validation rules
     * 
     * @return array
     */
    public function rules(): array
    {
        return [
            // 'name' => 'required|string|min:3|max:255',
            // 'email' => 'required|email|unique:users,email',
            // 'age' => 'required|numeric|min:18',
            // 'password' => 'required|min:8|confirmed',
        ];
    }

    /**
     * Get custom error messages
     * 
     * @return array
     */
    public function messages(): array
    {
        return [
            // 'name.required' => 'O nome é obrigatório',
            // 'email.email' => 'Email inválido',
        ];
    }

    /**
     * Validate request
     * 
     * @param Request $request
     * @return array|null Retorna erros ou null se válido
     */
    public function validate($request): ?array
    {
        return $request->validate($this->rules());
    }
}
PHP;

        return str_replace('{NAME}', $name, $stub);
    }
}
