<?php

namespace Trench94\Puppr\Concerns;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Trench94\Puppr\Models\Module;
use Trench94\Puppr\Puppr;

/**
 * Add to any Eloquent model (users, teams, tenants...) that can be granted modules.
 */
trait HasModules
{
    /**
     * The modules this model has been granted.
     */
    public function modules(): MorphToMany
    {
        return $this->morphToMany(
            config('puppr.model', Module::class),
            'accessor',
            config('puppr.tables.module_access', 'module_access'),
            'accessor_id',
            'module_id'
        )->withTimestamps();
    }

    /**
     * The active modules this model has been granted.
     */
    public function activeModules(): Collection
    {
        if ($this->relationLoaded('modules')) {
            return $this->modules->where('active', true)->values();
        }

        return $this->modules()->active()->get();
    }

    /**
     * Grant one or more modules to this model.
     *
     * @param  Module|string|int|array<int, Module|string|int>  ...$modules
     */
    public function grantModule(Module|string|int|array ...$modules): static
    {
        $ids = $this->moduleIds($modules);

        if ($ids !== []) {
            $this->modules()->syncWithoutDetaching($ids);
            $this->unsetRelation('modules');
        }

        return $this;
    }

    /**
     * Revoke one or more modules from this model.
     *
     * @param  Module|string|int|array<int, Module|string|int>  ...$modules
     */
    public function revokeModule(Module|string|int|array ...$modules): static
    {
        $ids = $this->moduleIds($modules, false);

        if ($ids !== []) {
            $this->modules()->detach($ids);
            $this->unsetRelation('modules');
        }

        return $this;
    }

    /**
     * Revoke every module from this model.
     */
    public function revokeAllModules(): static
    {
        $this->modules()->detach();
        $this->unsetRelation('modules');

        return $this;
    }

    /**
     * Replace this model's modules with the given set.
     *
     * @param  Module|string|int|array<int, Module|string|int>  ...$modules
     */
    public function syncModules(Module|string|int|array ...$modules): static
    {
        $this->modules()->sync($this->moduleIds($modules));
        $this->unsetRelation('modules');

        return $this;
    }

    /**
     * Determine whether the model has been granted the module AND the module is active.
     */
    public function hasModule(Module|string|int $module): bool
    {
        return Puppr::instance()->allows($this, $module);
    }

    /**
     * Determine whether the model has been granted ALL of the given active modules.
     *
     * @param  Module|string|int|array<int, Module|string|int>  ...$modules
     */
    public function hasAllModules(Module|string|int|array ...$modules): bool
    {
        return Puppr::instance()->allows($this, $modules);
    }

    /**
     * Determine whether the model has been granted ANY of the given active modules.
     *
     * @param  Module|string|int|array<int, Module|string|int>  ...$modules
     */
    public function hasAnyModule(Module|string|int|array ...$modules): bool
    {
        return Puppr::instance()->allowsAny($this, $modules);
    }

    /**
     * Determine whether the model has been granted the module, regardless of
     * whether the module is currently active.
     */
    public function isGrantedModule(Module|string|int $module): bool
    {
        $module = Puppr::instance()->find($module);

        if ($module === null) {
            return false;
        }

        if ($this->relationLoaded('modules')) {
            return $this->modules->contains($module->getKey());
        }

        return $this->modules()->whereKey($module->getKey())->exists();
    }

    /**
     * Resolve the given modules to their primary keys.
     *
     * @param  array<int, mixed>  $modules
     * @return array<int, int|string>
     */
    protected function moduleIds(array $modules, bool $failOnMissing = true): array
    {
        $puppr = Puppr::instance();
        $ids = [];

        foreach ($puppr->flatten($modules) as $module) {
            $resolved = $failOnMissing ? $puppr->findOrFail($module) : $puppr->find($module);

            if ($resolved !== null) {
                $ids[] = $resolved->getKey();
            }
        }

        return array_values(array_unique($ids));
    }
}
