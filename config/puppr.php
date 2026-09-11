<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Module model
    |--------------------------------------------------------------------------
    |
    | The Eloquent model used to represent a module. You may extend the
    | default model and point Puppr at your own class here.
    |
    */

    'model' => \Trench94\Puppr\Models\Module::class,

    /*
    |--------------------------------------------------------------------------
    | Table names
    |--------------------------------------------------------------------------
    |
    | "modules" stores the modules themselves. "module_access" is the
    | polymorphic pivot that records which users, teams or tenants have been
    | granted a module.
    |
    */

    'tables' => [
        'modules' => 'modules',
        'module_access' => 'module_access',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default accessor model
    |--------------------------------------------------------------------------
    |
    | The model used by the puppr:grant / puppr:revoke artisan commands when
    | no --model option is provided. It must use the HasModules trait.
    |
    */

    'user_model' => 'App\\Models\\User',

    /*
    |--------------------------------------------------------------------------
    | Unknown modules
    |--------------------------------------------------------------------------
    |
    | When a check references a module that has not been created, Puppr
    | denies access by default. Set this to true to throw a
    | ModuleNotFoundException instead, which is useful while developing.
    |
    */

    'throw_on_missing_module' => false,

    /*
    |--------------------------------------------------------------------------
    | Middleware aliases
    |--------------------------------------------------------------------------
    |
    | Route middleware aliases registered by the package.
    |   module:billing,reports      -> requires ALL listed modules
    |   module.any:billing,reports  -> requires ANY listed module
    |
    */

    'middleware' => [
        'all' => 'module',
        'any' => 'module.any',
    ],

    /*
    |--------------------------------------------------------------------------
    | Gate ability
    |--------------------------------------------------------------------------
    |
    | The gate ability name registered by Puppr, so that you may write
    | $user->can('module', 'billing') or @can('module', 'billing').
    | Set to null to disable the gate integration.
    |
    */

    'gate_ability' => 'module',

    /*
    |--------------------------------------------------------------------------
    | Blade directives
    |--------------------------------------------------------------------------
    |
    | Register the @module / @anymodule Blade directives.
    |
    */

    'blade_directives' => true,

];
