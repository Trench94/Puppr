<?php

namespace Trench94\Puppr;

use App\Packages\trench94\puppr\src\Console\InstallPuppr;
use Illuminate\Support\ServiceProvider;

class PupprServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        /*
         * Optional methods to load your package assets
         */
        // $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'puppr');
        // $this->loadViewsFrom(__DIR__.'/../resources/views', 'puppr');
        // $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        //$this->loadRoutesFrom(__DIR__.'/routes.php');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('puppr.php'),
            ], 'config');

            // Publishing the views.
            /*$this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/puppr'),
            ], 'views');*/

            // Publishing assets.
            /*$this->publishes([
                __DIR__.'/../resources/assets' => public_path('vendor/puppr'),
            ], 'assets');*/

            // Publishing the translation files.
            /*$this->publishes([
                __DIR__.'/../resources/lang' => resource_path('lang/vendor/puppr'),
            ], 'lang');*/

            // Register the command to install via the CLI
            if ($this->app->runningInConsole()) {
                $this->commands([
                    InstallPuppr::class,
                ]);
            }

            // Registering package commands.
            // $this->commands([]);
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'puppr');

        // Register the main class to use with the facade
        $this->app->singleton('puppr', function () {
            return new Puppr;
        });
    }
}
