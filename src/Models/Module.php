<?php

namespace Trench94\Puppr\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property bool $active
 */
class Module extends Model
{
    /**
     * @var array<int, string>
     */
    protected $fillable = ['name', 'slug', 'description', 'active'];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'active' => 'boolean',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'active' => true,
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable(config('puppr.tables.modules', 'modules'));
    }

    protected static function booted(): void
    {
        static::saving(function (self $module) {
            if (empty($module->slug)) {
                $module->slug = static::slugFor($module->name);
            }
        });
    }

    /**
     * Normalise a module name into the slug that identifies it.
     */
    public static function slugFor(string $name): string
    {
        return Str::slug($name);
    }

    /**
     * Scope a query to active modules only.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    /**
     * Scope a query to inactive modules only.
     */
    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('active', false);
    }

    /**
     * Scope a query to the module identified by the given name or slug.
     */
    public function scopeNamed(Builder $query, string $name): Builder
    {
        return $query->where(function (Builder $query) use ($name) {
            $query->where('slug', static::slugFor($name))->orWhere('name', $name);
        });
    }

    /**
     * The models of the given class that have been granted this module.
     *
     * @param  class-string<Model>  $class
     */
    public function accessors(string $class): MorphToMany
    {
        return $this->morphedByMany(
            $class,
            'accessor',
            config('puppr.tables.module_access', 'module_access'),
            'module_id'
        )->withTimestamps();
    }

    public function isActive(): bool
    {
        return (bool) $this->active;
    }

    public function activate(): static
    {
        $this->forceFill(['active' => true])->save();

        return $this;
    }

    public function deactivate(): static
    {
        $this->forceFill(['active' => false])->save();

        return $this;
    }
}
