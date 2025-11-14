<?php

namespace Alphavel\Core\Console\Commands;

use Alphavel\Core\Console\Command;

/**
 * Make Model Command
 */
class MakeModelCommand extends Command
{
    protected string $signature = 'make:model';

    protected string $description = 'Create a new model class';

    public function handle(): int
    {
        $name = $this->ask('Model name');

        if (! $name) {
            $this->error('Model name is required.');

            return 1;
        }

        $table = $this->ask('Table name', strtolower($name) . 's');

        $withTimestamps = $this->confirm('Use timestamps (created_at, updated_at)?', true);
        $withSoftDeletes = $this->confirm('Use soft deletes (deleted_at)?', false);

        $stub = $this->getStub($name, $table, $withTimestamps, $withSoftDeletes);

        $path = dirname(__DIR__, 2) . "/Models/{$name}.php";

        if (file_exists($path)) {
            if (! $this->confirm('Model already exists. Overwrite?')) {
                $this->warn('Operation cancelled.');

                return 0;
            }
        }

        file_put_contents($path, $stub);
        $this->info("Model created successfully: {$name}");
        $this->comment("Location: app/Models/{$name}.php");

        return 0;
    }

    private function getStub(string $name, string $table, bool $timestamps, bool $softDeletes): string
    {
        $timestampsLine = $timestamps
            ? 'protected static $timestamps = true;'
            : 'protected static $timestamps = false;';

        $softDeletesLine = $softDeletes
            ? 'protected static $useSoftDeletes = true;'
            : '';

        $softDeletesComment = $softDeletes
            ? "\n     * Soft deletes enabled - uses 'deleted_at' column"
            : '';

        return <<<PHP
<?php

/**
 * {$name} Model
 * 
 * Table: {$table}{$softDeletesComment}
 */
class {$name} extends Model
{
    protected static \$table = '{$table}';
    protected static \$primaryKey = 'id';
    {$timestampsLine}
    {$softDeletesLine}

    /**
     * Fillable attributes
     */
    protected static \$fillable = [
        // 'name', 'email', 'status'
    ];

    /**
     * Hidden attributes (not in toArray())
     */
    protected static \$hidden = [
        // 'password', 'token'
    ];

    // ============================================
    // Relationships
    // ============================================

    /**
     * Example: One-to-Many relationship
     * 
     * public function posts()
     * {
     *     return \$this->hasMany(Post::class);
     * }
     */

    /**
     * Example: Belongs-To relationship
     * 
     * public function user()
     * {
     *     return \$this->belongsTo(User::class);
     * }
     */

    /**
     * Example: One-to-One relationship
     * 
     * public function profile()
     * {
     *     return \$this->hasOne(Profile::class);
     * }
     */

    // ============================================
    // Accessors (Getters)
    // ============================================

    /**
     * Example: Accessor for full_name attribute
     * 
     * public function getFullNameAttribute()
     * {
     *     return \$this->first_name . ' ' . \$this->last_name;
     * }
     * 
     * Usage: \$user->full_name
     */

    // ============================================
    // Mutators (Setters)
    // ============================================

    /**
     * Example: Mutator for password attribute
     * 
     * public function setPasswordAttribute(\$value)
     * {
     *     \$this->attributes['password'] = password_hash(\$value, PASSWORD_BCRYPT);
     * }
     * 
     * Usage: \$user->password = '123456'; // Auto-hashed
     */

    // ============================================
    // Scopes
    // ============================================

    /**
     * Example: Query scope
     * 
     * public static function scopeActive(\$query)
     * {
     *     return \$query->where('status', 'active');
     * }
     * 
     * Usage: {$name}::active()->get();
     */
}

PHP;
    }
}
