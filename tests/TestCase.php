<?php

namespace Trench94\Puppr\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;
use Trench94\Puppr\Models\Module;
use Trench94\Puppr\Puppr;
use Trench94\Puppr\PupprFacade;
use Trench94\Puppr\PupprServiceProvider;
use Trench94\Puppr\Tests\Fixtures\Team;
use Trench94\Puppr\Tests\Fixtures\User;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase();
    }

    protected function getPackageProviders($app): array
    {
        return [PupprServiceProvider::class];
    }

    protected function getPackageAliases($app): array
    {
        return ['Puppr' => PupprFacade::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        $app['config']->set('auth.providers.users.model', User::class);
        $app['config']->set('puppr.user_model', User::class);
        $app['config']->set('view.paths', [__DIR__.'/views']);
    }

    protected function setUpDatabase(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        $migration = include __DIR__.'/../database/migrations/create_puppr_tables.php.stub';
        $migration->up();
    }

    protected function puppr(): Puppr
    {
        return $this->app->make(Puppr::class);
    }

    protected function createUser(string $name = 'Luke'): User
    {
        return User::create(['name' => $name]);
    }

    protected function createTeam(string $name = 'Acme'): Team
    {
        return Team::create(['name' => $name]);
    }

    protected function createModule(string $name = 'Billing', bool $active = true): Module
    {
        return $this->puppr()->create($name, ['active' => $active]);
    }
}
