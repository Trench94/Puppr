<?php

namespace Trench94\Puppr;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Trench94\Puppr\Models\Module create(string $name, array $attributes = [])
 * @method static \Trench94\Puppr\Models\Module|null find(\Trench94\Puppr\Models\Module|string|int|null $module)
 * @method static \Trench94\Puppr\Models\Module findOrFail(\Trench94\Puppr\Models\Module|string|int $module)
 * @method static bool exists(\Trench94\Puppr\Models\Module|string|int $module)
 * @method static bool isActive(\Trench94\Puppr\Models\Module|string|int $module)
 * @method static \Trench94\Puppr\Models\Module activate(\Trench94\Puppr\Models\Module|string|int $module)
 * @method static \Trench94\Puppr\Models\Module deactivate(\Trench94\Puppr\Models\Module|string|int $module)
 * @method static bool delete(\Trench94\Puppr\Models\Module|string|int $module)
 * @method static \Illuminate\Database\Eloquent\Collection all()
 * @method static \Illuminate\Database\Eloquent\Collection active()
 * @method static \Illuminate\Database\Eloquent\Model grant(\Illuminate\Database\Eloquent\Model $accessor, mixed ...$modules)
 * @method static \Illuminate\Database\Eloquent\Model revoke(\Illuminate\Database\Eloquent\Model $accessor, mixed ...$modules)
 * @method static \Trench94\Puppr\Puppr bypassUsing(\Closure $callback)
 * @method static \Trench94\Puppr\Puppr clearBypasses()
 * @method static bool allows(\Illuminate\Database\Eloquent\Model|null $accessor, mixed ...$modules)
 * @method static bool allowsAny(\Illuminate\Database\Eloquent\Model|null $accessor, mixed ...$modules)
 * @method static bool denies(\Illuminate\Database\Eloquent\Model|null $accessor, mixed ...$modules)
 * @method static void authorize(\Illuminate\Database\Eloquent\Model|null $accessor, mixed ...$modules)
 * @method static void authorizeAny(\Illuminate\Database\Eloquent\Model|null $accessor, mixed ...$modules)
 *
 * @see \Trench94\Puppr\Puppr
 */
class PupprFacade extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return Puppr::class;
    }
}
