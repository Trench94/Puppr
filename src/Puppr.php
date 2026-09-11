<?php

namespace Trench94\Puppr;

use Closure;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use LogicException;
use Trench94\Puppr\Concerns\HasModules;
use Trench94\Puppr\Exceptions\ModuleAccessDeniedException;
use Trench94\Puppr\Exceptions\ModuleNotFoundException;
use Trench94\Puppr\Models\Module;

class Puppr
{
    /**
     * Callbacks that may short-circuit every access check.
     *
     * @var array<int, Closure>
     */
    protected array $bypassCallbacks = [];

    /**
     * Resolve the Puppr manager from the container.
     */
    public static function instance(): self
    {
        return app(self::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Module management
    |--------------------------------------------------------------------------
    */

    /**
     * The module model class name.
     *
     * @return class-string<Module>
     */
    public function modelClass(): string
    {
        return config('puppr.model', Module::class);
    }

    /**
     * A new query builder for modules.
     */
    public function query(): \Illuminate\Database\Eloquent\Builder
    {
        return $this->modelClass()::query();
    }

    /**
     * Create a module, or return the existing module with the same name.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create(string $name, array $attributes = []): Module
    {
        $slug = $attributes['slug'] ?? $this->modelClass()::slugFor($name);

        return $this->query()->firstOrCreate(
            ['slug' => $slug],
            array_merge(['name' => $name, 'active' => true], $attributes)
        );
    }

    /**
     * Find a module by instance, primary key, slug or name.
     */
    public function find(Module|string|int|null $module): ?Module
    {
        if ($module instanceof Module) {
            return $module;
        }

        if ($module === null || $module === '') {
            return null;
        }

        if (is_int($module)) {
            return $this->query()->find($module);
        }

        return $this->query()->named($module)->first();
    }

    /**
     * Find a module or throw a ModuleNotFoundException.
     */
    public function findOrFail(Module|string|int $module): Module
    {
        return $this->find($module) ?? throw ModuleNotFoundException::named($this->describe($module));
    }

    public function exists(Module|string|int $module): bool
    {
        return $this->find($module) !== null;
    }

    public function isActive(Module|string|int $module): bool
    {
        $module = $this->find($module);

        return $module !== null && $module->isActive();
    }

    public function activate(Module|string|int $module): Module
    {
        return $this->findOrFail($module)->activate();
    }

    public function deactivate(Module|string|int $module): Module
    {
        return $this->findOrFail($module)->deactivate();
    }

    public function delete(Module|string|int $module): bool
    {
        return (bool) $this->findOrFail($module)->delete();
    }

    /**
     * All modules.
     */
    public function all(): Collection
    {
        return $this->query()->orderBy('name')->get();
    }

    /**
     * All active modules.
     */
    public function active(): Collection
    {
        return $this->query()->active()->orderBy('name')->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Granting access
    |--------------------------------------------------------------------------
    */

    /**
     * Grant modules to an accessor (a model using HasModules).
     *
     * @param  Module|string|int|array<int, Module|string|int>  ...$modules
     */
    public function grant(Model $accessor, Module|string|int|array ...$modules): Model
    {
        return $this->accessor($accessor)->grantModule(...$modules);
    }

    /**
     * Revoke modules from an accessor.
     *
     * @param  Module|string|int|array<int, Module|string|int>  ...$modules
     */
    public function revoke(Model $accessor, Module|string|int|array ...$modules): Model
    {
        return $this->accessor($accessor)->revokeModule(...$modules);
    }

    /*
    |--------------------------------------------------------------------------
    | Checking access
    |--------------------------------------------------------------------------
    */

    /**
     * Register a callback that may bypass module checks entirely.
     *
     * Return true from the callback to allow access to every module, or
     * null/false to fall through to the normal check. Useful for super admins.
     *
     * @param  Closure(Model|null, Module): (bool|null)  $callback
     */
    public function bypassUsing(Closure $callback): static
    {
        $this->bypassCallbacks[] = $callback;

        return $this;
    }

    /**
     * Remove all registered bypass callbacks.
     */
    public function clearBypasses(): static
    {
        $this->bypassCallbacks = [];

        return $this;
    }

    /**
     * Determine whether the accessor may use ALL of the given modules.
     *
     * A module may be used only if it exists, is active and has been granted
     * to the accessor (or a bypass callback allows it). A null accessor (guest)
     * is always denied.
     *
     * @param  Module|string|int|array<int, Module|string|int>  ...$modules
     */
    public function allows(?Model $accessor, Module|string|int|array ...$modules): bool
    {
        $modules = $this->flatten($modules);

        if ($modules === []) {
            return false;
        }

        foreach ($modules as $module) {
            if (! $this->allowsOne($accessor, $module)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine whether the accessor may use ANY of the given modules.
     *
     * @param  Module|string|int|array<int, Module|string|int>  ...$modules
     */
    public function allowsAny(?Model $accessor, Module|string|int|array ...$modules): bool
    {
        foreach ($this->flatten($modules) as $module) {
            if ($this->allowsOne($accessor, $module)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine whether the accessor is denied ANY of the given modules.
     *
     * @param  Module|string|int|array<int, Module|string|int>  ...$modules
     */
    public function denies(?Model $accessor, Module|string|int|array ...$modules): bool
    {
        return ! $this->allows($accessor, ...$modules);
    }

    /**
     * Throw a ModuleAccessDeniedException unless the accessor may use ALL of the modules.
     *
     * @param  Module|string|int|array<int, Module|string|int>  ...$modules
     *
     * @throws ModuleAccessDeniedException
     */
    public function authorize(?Model $accessor, Module|string|int|array ...$modules): void
    {
        if (! $this->allows($accessor, ...$modules)) {
            throw ModuleAccessDeniedException::forModules($this->describeAll($modules), true);
        }
    }

    /**
     * Throw a ModuleAccessDeniedException unless the accessor may use ANY of the modules.
     *
     * @param  Module|string|int|array<int, Module|string|int>  ...$modules
     *
     * @throws ModuleAccessDeniedException
     */
    public function authorizeAny(?Model $accessor, Module|string|int|array ...$modules): void
    {
        if (! $this->allowsAny($accessor, ...$modules)) {
            throw ModuleAccessDeniedException::forModules($this->describeAll($modules), false);
        }
    }

    /**
     * Check a single module against an accessor.
     */
    protected function allowsOne(?Model $accessor, Module|string|int $module): bool
    {
        $resolved = $this->find($module);

        if ($resolved === null) {
            if (config('puppr.throw_on_missing_module', false)) {
                throw ModuleNotFoundException::named($this->describe($module));
            }

            return false;
        }

        if (! $resolved->isActive()) {
            return false;
        }

        foreach ($this->bypassCallbacks as $callback) {
            if ($callback($accessor, $resolved) === true) {
                return true;
            }
        }

        if ($accessor === null) {
            return false;
        }

        return $this->accessor($accessor)->isGrantedModule($resolved);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Flatten a variadic list of modules (which may itself contain arrays).
     *
     * @param  array<int, mixed>  $modules
     * @return array<int, Module|string|int>
     */
    public function flatten(array $modules): array
    {
        $flat = [];

        foreach (Arr::flatten($modules) as $module) {
            if ($module instanceof Module || is_int($module)) {
                $flat[] = $module;
            } elseif (is_string($module) && trim($module) !== '') {
                $flat[] = trim($module);
            }
        }

        return $flat;
    }

    /**
     * A human readable name for the given module reference.
     */
    public function describe(Module|string|int $module): string
    {
        return $module instanceof Module ? $module->slug : (string) $module;
    }

    /**
     * @param  array<int, mixed>  $modules
     * @return array<int, string>
     */
    protected function describeAll(array $modules): array
    {
        return array_map(fn ($module) => $this->describe($module), $this->flatten($modules));
    }

    /**
     * Ensure the given model uses the HasModules trait.
     *
     * @return Model&HasModules
     */
    protected function accessor(Model $accessor): Model
    {
        if (! in_array(HasModules::class, class_uses_recursive($accessor), true)) {
            throw new LogicException(sprintf(
                '[%s] must use the %s trait before it can be granted modules.',
                $accessor::class,
                HasModules::class
            ));
        }

        return $accessor;
    }
}
