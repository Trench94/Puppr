<?php

namespace Trench94\Puppr\Tests\Feature;

use Illuminate\Support\Facades\Route;
use Trench94\Puppr\Http\Middleware\EnsureModuleAccess;
use Trench94\Puppr\PupprFacade as Puppr;
use Trench94\Puppr\Tests\TestCase;

class MiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Puppr::create('Billing');
        Puppr::create('Reports');

        Route::middleware('module:billing')->get('/billing', fn () => 'billing ok');
        Route::middleware('module:billing,reports')->get('/all', fn () => 'all ok');
        Route::middleware('module:billing|reports')->get('/all-pipe', fn () => 'all ok');
        Route::middleware('module.any:billing,reports')->get('/any', fn () => 'any ok');
        Route::middleware(EnsureModuleAccess::using('reports'))->get('/class', fn () => 'class ok');
    }

    public function test_guests_receive_a_403(): void
    {
        $this->get('/billing')->assertForbidden();
    }

    public function test_users_without_the_module_receive_a_403(): void
    {
        $this->actingAs($this->createUser())->get('/billing')->assertForbidden();
    }

    public function test_users_with_the_module_are_allowed(): void
    {
        $user = $this->createUser()->grantModule('billing');

        $this->actingAs($user)->get('/billing')->assertOk()->assertSee('billing ok');
    }

    public function test_inactive_modules_block_everyone(): void
    {
        $user = $this->createUser()->grantModule('billing');
        Puppr::deactivate('billing');

        $this->actingAs($user)->get('/billing')->assertForbidden();
    }

    public function test_all_listed_modules_are_required(): void
    {
        $user = $this->createUser()->grantModule('billing');

        $this->actingAs($user)->get('/all')->assertForbidden();
        $this->actingAs($user)->get('/all-pipe')->assertForbidden();

        $user->grantModule('reports');

        $this->actingAs($user)->get('/all')->assertOk();
        $this->actingAs($user)->get('/all-pipe')->assertOk();
    }

    public function test_any_middleware_requires_only_one_module(): void
    {
        $user = $this->createUser();
        $this->actingAs($user)->get('/any')->assertForbidden();

        $user->grantModule('reports');
        $this->actingAs($user)->get('/any')->assertOk()->assertSee('any ok');
    }

    public function test_the_middleware_can_be_referenced_by_class(): void
    {
        $user = $this->createUser()->grantModule('reports');

        $this->actingAs($user)->get('/class')->assertOk();
    }
}
