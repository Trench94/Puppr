<?php

namespace Trench94\Puppr;

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\Compilers\BladeCompiler;
use Trench94\Puppr\Console\ActivateModule;
use Trench94\Puppr\Console\DeactivateModule;
use Trench94\Puppr\Console\GrantModule;
use Trench94\Puppr\Console\InstallPuppr;
use Trench94\Puppr\Console\ListModules;
use Trench94\Puppr\Console\MakeModule;
use Trench94\Puppr\Console\RevokeModule;
use Trench94\Puppr\Http\Middleware\EnsureAnyModuleAccess;
use Trench94\Puppr\Http\Middleware\EnsureModuleAccess;

class PupprServiceProvider extends ServiceProvider
{
    /**
     * Register the application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/puppr.php', 'puppr');

        $this->app->singleton(Puppr::class, fn () => new Puppr);
        $this->app->alias(Puppr::class, 'puppr');
    }

    /**
     * Bootstrap the application services.
     */
    public function boot(): void
    {
        $this->registerPublishing();
        $this->registerCommands();
        $this->registerMiddleware();
        $this->registerGate();
        $this->registerBladeDirectives();
    }

    protected function registerPublishing(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/puppr.php' => config_path('puppr.php'),
        ], 'puppr-config');

        if (! $this->migrationExists('create_puppr_tables')) {
            $this->publishes([
                __DIR__.'/../database/migrations/create_puppr_tables.php.stub' => database_path(
                    'migrations/'.date('Y_m_d_His').'_create_puppr_tables.php'
                ),
            ], 'puppr-migrations');
        }
    }

    protected function registerCommands(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            InstallPuppr::class,
            MakeModule::class,
            ListModules::class,
            ActivateModule::class,
            DeactivateModule::class,
            GrantModule::class,
            RevokeModule::class,
        ]);
    }

    protected function registerMiddleware(): void
    {
        $router = $this->app->make(Router::class);

        if ($alias = config('puppr.middleware.all')) {
            $router->aliasMiddleware($alias, EnsureModuleAccess::class);
        }

        if ($alias = config('puppr.middleware.any')) {
            $router->aliasMiddleware($alias, EnsureAnyModuleAccess::class);
        }
    }

    protected function registerGate(): void
    {
        $ability = config('puppr.gate_ability');

        if (! $ability) {
            return;
        }

        $this->app->make(Gate::class)->define($ability, function ($user, ...$modules) {
            return $this->app->make(Puppr::class)->allows($user, ...$modules);
        });
    }

    protected function registerBladeDirectives(): void
    {
        if (! config('puppr.blade_directives', true)) {
            return;
        }

        $this->callAfterResolving('blade.compiler', function (BladeCompiler $blade) {
            $blade->if('module', function (...$modules) {
                return $this->app->make(Puppr::class)->allows($this->currentUser(), ...$modules);
            });

            $blade->if('anymodule', function (...$modules) {
                return $this->app->make(Puppr::class)->allowsAny($this->currentUser(), ...$modules);
            });
        });
    }

    protected function currentUser(): ?\Illuminate\Database\Eloquent\Model
    {
        $user = $this->app->make('auth')->user();

        return $user instanceof \Illuminate\Database\Eloquent\Model ? $user : null;
    }

    /**
     * Determine whether a migration with the given suffix has already been published.
     */
    protected function migrationExists(string $name): bool
    {
        return count(glob(database_path("migrations/*_{$name}.php")) ?: []) > 0;
    }
}
