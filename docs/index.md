![Puppr logo](puppr-small.png)

# Puppr

**Feature-module access control for modular Laravel SaaS applications.**

Define the modules your product is made of, grant them to users, teams or tenants, and guard your routes, views and gates with a single line.

## Installation

```bash
composer require trench94/puppr
php artisan puppr:install
php artisan migrate
```

Add the trait to your `User` model (or any model that can own modules):

```php
use Trench94\Puppr\Concerns\HasModules;

class User extends Authenticatable
{
    use HasModules;
}
```

## Quick start

```php
use Trench94\Puppr\PupprFacade as Puppr;

Puppr::create('Billing');
Puppr::create('Advanced Reports');

$user->grantModule('billing', 'advanced-reports');

$user->hasModule('billing');                 // true
Puppr::allows($user, 'advanced-reports');    // true

Route::middleware('module:billing')->get('/invoices', InvoiceController::class);
```

```blade
@module('advanced-reports')
    <a href="{{ route('reports') }}">Reports</a>
@endmodule
```

## How it works

A check passes only when the module **exists**, is **active**, and has been **granted** to the accessor.

- Deactivate a module and it disappears for everyone: `Puppr::deactivate('billing')`.
- Grants are polymorphic, so `Team`, `Tenant` or `Organisation` models work exactly like `User`.
- Super admins? `Puppr::bypassUsing(fn ($user) => $user?->is_super_admin)`.

## Guarding your app

| Where | How |
|-------|-----|
| Routes | `Route::middleware('module:billing,reports')` (all) / `module.any:billing,reports` (any) |
| Gates | `$user->can('module', 'billing')`, `Gate::authorize('module', 'billing')` |
| Blade | `@module('billing') ... @elsemodule ... @endmodule`, `@anymodule(...)`, `@unlessmodule(...)` |
| Code | `Puppr::allows()`, `Puppr::allowsAny()`, `Puppr::authorize()` |

## Console

```bash
php artisan puppr:make "Advanced Reports" --description="Charts and exports"
php artisan puppr:list
php artisan puppr:activate advanced-reports
php artisan puppr:deactivate advanced-reports
php artisan puppr:grant advanced-reports 42
php artisan puppr:revoke advanced-reports 42
```

## Full documentation

The complete guide, configuration reference and API live in the [README on GitHub](https://github.com/Trench94/Puppr#readme).

## Support or contact

Found a bug or have an idea? [Open an issue](https://github.com/Trench94/Puppr/issues). Security issues should follow [SECURITY.md](https://github.com/Trench94/Puppr/blob/main/SECURITY.md).
