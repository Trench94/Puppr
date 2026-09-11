<div align="center">

<img src="./logo.png" alt="Puppr logo" width="180"/>

# Puppr

**Feature-module access control for modular Laravel SaaS applications.**

Define the modules your product is made of, grant them to users, teams or tenants, and guard your routes, views and gates with a single line.

[![Tests](https://github.com/Trench94/Puppr/actions/workflows/tests.yml/badge.svg)](https://github.com/Trench94/Puppr/actions/workflows/tests.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/trench94/puppr.svg)](https://packagist.org/packages/trench94/puppr)
[![Total Downloads](https://img.shields.io/packagist/dt/trench94/puppr.svg)](https://packagist.org/packages/trench94/puppr)
[![License](https://img.shields.io/packagist/l/trench94/puppr.svg)](LICENSE)

</div>

---

## Why Puppr?

Most SaaS products are sold as a set of features: *Billing*, *Reports*, *API access*, *Chat*... Roles and permissions describe **what a user may do**, but they get awkward when you need to describe **which parts of the product a customer has bought or been given**.

Puppr models exactly that. A **module** is a named feature of your application. You **grant** modules to users (or teams, tenants, organisations, anything Eloquent). Puppr then answers one question everywhere in your app:

> *Is this module switched on, and does this user have it?*

- 🧩 **Modules** are plain Eloquent records: create them in a seeder, an admin panel or from the console.
- 🔌 **Kill switch**: deactivate a module and it disappears for everyone, without touching a single grant.
- 👥 **Polymorphic grants**: give modules to users today and to teams tomorrow, with the same trait.
- 🛡️ **Guards everywhere**: route middleware, gate ability, Blade directives, and a facade for everything else.
- 🖥️ **Console tooling**: create, list, activate, grant and revoke modules from artisan.
- 🪶 **No opinions** about billing, plans or roles. Puppr composes with Cashier, Spatie Permission or anything else you already use.

## Requirements

| Puppr | PHP | Laravel |
|-------|-----|---------|
| 1.x   | 8.1+ | 10, 11, 12 |

## Installation

Install the package with Composer:

```bash
composer require trench94/puppr
```

Publish the config file and migration, then migrate:

```bash
php artisan puppr:install
php artisan migrate
```

Finally, add the `HasModules` trait to any model that can be granted modules. Usually that is your `User` model:

```php
use Illuminate\Foundation\Auth\User as Authenticatable;
use Trench94\Puppr\Concerns\HasModules;

class User extends Authenticatable
{
    use HasModules;
}
```

That's it. Puppr auto-registers its service provider, facade, middleware, gate ability and Blade directives.

## Quick start

```php
use Trench94\Puppr\PupprFacade as Puppr;

// 1. Define the modules your product offers
Puppr::create('Billing');
Puppr::create('Advanced Reports', ['description' => 'Charts, exports and scheduled reports']);

// 2. Grant them
$user->grantModule('billing', 'advanced-reports');

// 3. Check them
$user->hasModule('billing');            // true
Puppr::allows($user, 'advanced-reports'); // true

// 4. Guard things
Route::middleware('module:billing')->get('/invoices', InvoiceController::class);
```

```blade
@module('advanced-reports')
    <a href="{{ route('reports') }}">Reports</a>
@endmodule
```

## Usage

### Creating modules

A module has a `name`, a URL-friendly `slug` (generated from the name), an optional `description` and an `active` flag. Modules are referenced by slug or name everywhere, so `'Advanced Reports'` and `'advanced-reports'` are interchangeable.

```php
Puppr::create('Billing');                                  // active by default
Puppr::create('Beta Chat', ['active' => false]);           // hidden until you flip it on
Puppr::create('Reports', ['slug' => 'rpt']);               // custom slug
```

`create()` is idempotent, so it is safe to call from a seeder or a deploy script. You can also use the model directly:

```php
use Trench94\Puppr\Models\Module;

Module::create(['name' => 'Billing']);
Module::active()->get();
Module::named('billing')->first();
```

Or from the console:

```bash
php artisan puppr:make "Advanced Reports" --description="Charts and exports"
php artisan puppr:make "Beta Chat" --inactive
php artisan puppr:list
```

### Granting and revoking

Any model using `HasModules` gets a fluent API. Modules can be referenced by slug, name, id or `Module` instance, individually or as an array:

```php
$user->grantModule('billing');
$user->grantModule('billing', 'reports');
$user->grantModule(['billing', 'reports']);
$user->grantModule($module);

$user->revokeModule('reports');
$user->revokeAllModules();

$user->syncModules('billing', 'chat');   // replace the whole set

$user->modules;                          // Eloquent collection of granted modules
$user->activeModules();                  // only the ones currently switched on
```

The same works through the facade, which is handy in services and jobs:

```php
Puppr::grant($team, 'billing');
Puppr::revoke($team, 'billing');
```

Granting an unknown module throws a `ModuleNotFoundException`, so typos surface immediately. Granting the same module twice is a no-op.

From the console:

```bash
php artisan puppr:grant billing 42                       # user id 42
php artisan puppr:revoke billing 42
php artisan puppr:grant billing 7 --model="App\Models\Team"
```

### Checking access

A module check passes only when **all three** are true: the module exists, the module is active, and the accessor has been granted it. Guests (`null`) are always denied.

```php
$user->hasModule('billing');                    // granted AND active
$user->hasAllModules('billing', 'reports');
$user->hasAnyModule('billing', 'reports');
$user->isGrantedModule('billing');              // granted, even if the module is inactive

Puppr::allows($user, 'billing');
Puppr::allows($user, 'billing', 'reports');     // all required
Puppr::allowsAny($user, 'billing', 'reports');  // any is enough
Puppr::denies($user, 'billing');

Puppr::authorize($user, 'billing');             // throws ModuleAccessDeniedException (403)
Puppr::authorizeAny($user, 'billing', 'reports');
```

`ModuleAccessDeniedException` extends Laravel's `AuthorizationException`, so an uncaught one becomes a normal **403** response. Call `$e->modules()` to see which modules were checked.

### Route middleware

Two middleware aliases are registered. Separate multiple modules with commas (or pipes):

```php
// Requires ALL listed modules
Route::middleware('module:billing')->group(function () { ... });
Route::middleware('module:billing,reports')->get('/dashboard', ...);

// Requires ANY of the listed modules
Route::middleware('module.any:billing,reports')->get('/exports', ...);
```

Prefer class references? Use the helper:

```php
use Trench94\Puppr\Http\Middleware\EnsureModuleAccess;

Route::middleware(EnsureModuleAccess::using('billing', 'reports'))->get(...);
```

Puppr reads the user from `$request->user()`, so place the middleware after `auth` (or use it on authenticated groups).

### Gate ability

Puppr registers a `module` gate ability, so all of Laravel's authorization helpers just work:

```php
$user->can('module', 'billing');
$user->can('module', ['billing', 'reports']);   // all required
$user->cannot('module', 'chat');

Gate::authorize('module', 'billing');             // in a controller
$this->authorize('module', 'billing');            // with AuthorizesRequests
```

```blade
@can('module', 'billing')
    ...
@endcan
```

### Blade directives

```blade
@module('billing')
    <x-nav-link :href="route('billing')">Billing</x-nav-link>
@elsemodule
    <x-upgrade-banner module="billing" />
@endmodule

@module('billing', 'reports')   {{-- all required --}}
    ...
@endmodule

@anymodule('billing', 'reports')
    ...
@endanymodule

@unlessmodule('chat')
    <p>Chat is not part of your plan.</p>
@endmodule
```

### Switching modules on and off

Deactivating a module hides it from **everyone** without touching their grants. Reactivate it and all grants come back. Perfect for maintenance windows, gradual rollouts or pulling a feature quickly.

```php
Puppr::deactivate('beta-chat');
Puppr::isActive('beta-chat');   // false
Puppr::activate('beta-chat');

$module->deactivate();
$module->activate();
```

```bash
php artisan puppr:deactivate beta-chat
php artisan puppr:activate beta-chat
```

### Super admins and other bypasses

Sometimes a user should see every active module regardless of grants. Register a bypass callback in a service provider; return `true` to allow, or `null`/`false` to fall through to the normal check:

```php
use Trench94\Puppr\PupprFacade as Puppr;

public function boot(): void
{
    Puppr::bypassUsing(fn ($user, $module) => $user?->is_super_admin);
}
```

Bypasses never unlock **inactive** modules. The kill switch always wins.

### Granting modules to teams, tenants or organisations

`HasModules` works on any Eloquent model because grants are stored in a polymorphic pivot. To scope access by team rather than by user:

```php
class Team extends Model
{
    use HasModules;
}

$team->grantModule('billing');
$team->hasModule('billing');

Route::middleware('module:billing')  // still checks $request->user()
```

If your checks should run against the current team instead of the user, wrap it in a bypass or write a small middleware that calls `Puppr::authorize($request->user()->currentTeam, ...)`. You can also look at a module from the other side:

```php
$module->accessors(User::class)->get();   // every user granted this module
$module->accessors(Team::class)->count();
```

## Configuration

`php artisan puppr:install` publishes `config/puppr.php`:

| Key | Default | Purpose |
|-----|---------|---------|
| `model` | `Trench94\Puppr\Models\Module` | Swap in your own `Module` subclass. |
| `tables.modules` | `modules` | Table that stores modules. |
| `tables.module_access` | `module_access` | Polymorphic pivot table for grants. |
| `user_model` | `App\Models\User` | Default model for `puppr:grant` / `puppr:revoke`. |
| `throw_on_missing_module` | `false` | Throw instead of denying when a check names an unknown module. Handy in development. |
| `middleware.all` / `middleware.any` | `module` / `module.any` | Middleware aliases. Set to `null` to skip registration. |
| `gate_ability` | `module` | Gate ability name. Set to `null` to disable. |
| `blade_directives` | `true` | Register the Blade directives. |

Change the table names **before** running the migration.

## Artisan commands

| Command | Description |
|---------|-------------|
| `puppr:install [--force] [--migrate]` | Publish config and migration (optionally run migrations). |
| `puppr:make {name} [--description=] [--inactive]` | Create a module. |
| `puppr:list [--active]` | Show all modules in a table. |
| `puppr:activate {module}` | Switch a module on. |
| `puppr:deactivate {module}` | Switch a module off for everyone. |
| `puppr:grant {module} {id} [--model=]` | Grant a module to a user (or other model). |
| `puppr:revoke {module} {id} [--model=]` | Revoke a module. |

## Testing

```bash
composer install
composer test
```

The suite runs against an in-memory SQLite database using Orchestra Testbench. CI runs it on PHP 8.1 to 8.4 across Laravel 10, 11 and 12.

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for what has changed recently.

## Contributing

Contributions are welcome! Please read [CONTRIBUTING.md](CONTRIBUTING.md) first. Bugs and ideas go in the [issue tracker](https://github.com/Trench94/Puppr/issues).

## Security

If you discover a security issue, please follow the process in [SECURITY.md](SECURITY.md) instead of opening a public issue.

## Credits

- [Luke Marshall](https://github.com/Trench94)
- [All contributors](https://github.com/Trench94/Puppr/contributors)

## License

Puppr is open-source software licensed under the [MIT license](LICENSE).
