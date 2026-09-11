<?php

namespace Trench94\Puppr\Tests\Unit;

use LogicException;
use Trench94\Puppr\Exceptions\ModuleAccessDeniedException;
use Trench94\Puppr\Exceptions\ModuleNotFoundException;
use Trench94\Puppr\Models\Module;
use Trench94\Puppr\PupprFacade as Puppr;
use Trench94\Puppr\Tests\Fixtures\PlainModel;
use Trench94\Puppr\Tests\TestCase;

class PupprTest extends TestCase
{
    public function test_create_is_idempotent(): void
    {
        $first = Puppr::create('Billing', ['description' => 'Invoices and payments']);
        $second = Puppr::create('billing');

        $this->assertTrue($first->is($second));
        $this->assertSame('Invoices and payments', $second->description);
        $this->assertSame(1, Module::count());
    }

    public function test_find_and_exists(): void
    {
        $module = Puppr::create('Billing');

        $this->assertTrue($module->is(Puppr::find('billing')));
        $this->assertTrue($module->is(Puppr::find('Billing')));
        $this->assertTrue($module->is(Puppr::find($module->id)));
        $this->assertTrue($module->is(Puppr::find($module)));
        $this->assertNull(Puppr::find('nope'));
        $this->assertNull(Puppr::find(null));
        $this->assertTrue(Puppr::exists('billing'));
        $this->assertFalse(Puppr::exists('nope'));
    }

    public function test_find_or_fail_throws_for_unknown_modules(): void
    {
        $this->expectException(ModuleNotFoundException::class);
        $this->expectExceptionMessage('Module [nope] does not exist');

        Puppr::findOrFail('nope');
    }

    public function test_activate_deactivate_and_delete(): void
    {
        Puppr::create('Billing');

        Puppr::deactivate('billing');
        $this->assertFalse(Puppr::isActive('billing'));

        Puppr::activate('billing');
        $this->assertTrue(Puppr::isActive('billing'));

        Puppr::delete('billing');
        $this->assertFalse(Puppr::exists('billing'));
        $this->assertFalse(Puppr::isActive('billing'));
    }

    public function test_all_and_active(): void
    {
        Puppr::create('Reports');
        Puppr::create('Billing');
        Puppr::create('Chat', ['active' => false]);

        $this->assertSame(['billing', 'chat', 'reports'], Puppr::all()->pluck('slug')->all());
        $this->assertSame(['billing', 'reports'], Puppr::active()->pluck('slug')->all());
    }

    public function test_grant_and_revoke_through_the_manager(): void
    {
        Puppr::create('Billing');
        $user = $this->createUser();

        Puppr::grant($user, 'billing');
        $this->assertTrue(Puppr::allows($user, 'billing'));

        Puppr::revoke($user, 'billing');
        $this->assertFalse(Puppr::allows($user, 'billing'));
    }

    public function test_allows_denies_and_allows_any(): void
    {
        Puppr::create('Billing');
        Puppr::create('Reports');
        $user = $this->createUser();
        Puppr::grant($user, 'billing');

        $this->assertTrue(Puppr::allows($user, 'billing'));
        $this->assertFalse(Puppr::allows($user, 'reports'));
        $this->assertFalse(Puppr::allows($user, 'billing', 'reports'));
        $this->assertFalse(Puppr::allows($user, ['billing', 'reports']));
        $this->assertTrue(Puppr::allowsAny($user, 'billing', 'reports'));
        $this->assertTrue(Puppr::denies($user, 'reports'));
        $this->assertFalse(Puppr::denies($user, 'billing'));
    }

    public function test_guests_are_always_denied(): void
    {
        Puppr::create('Billing');

        $this->assertFalse(Puppr::allows(null, 'billing'));
        $this->assertFalse(Puppr::allowsAny(null, 'billing'));
    }

    public function test_an_empty_module_list_is_denied(): void
    {
        $user = $this->createUser();

        $this->assertFalse(Puppr::allows($user));
        $this->assertFalse(Puppr::allows($user, ''));
    }

    public function test_unknown_modules_are_denied_by_default(): void
    {
        $user = $this->createUser();

        $this->assertFalse(Puppr::allows($user, 'nope'));
    }

    public function test_unknown_modules_can_throw_when_configured(): void
    {
        config()->set('puppr.throw_on_missing_module', true);
        $user = $this->createUser();

        $this->expectException(ModuleNotFoundException::class);

        Puppr::allows($user, 'nope');
    }

    public function test_authorize_throws_a_403_exception(): void
    {
        Puppr::create('Billing');
        Puppr::create('Reports');
        $user = $this->createUser();
        Puppr::grant($user, 'billing');

        Puppr::authorize($user, 'billing');
        Puppr::authorizeAny($user, 'billing', 'reports');

        try {
            Puppr::authorize($user, 'billing', 'reports');
            $this->fail('Expected ModuleAccessDeniedException');
        } catch (ModuleAccessDeniedException $e) {
            $this->assertSame(['billing', 'reports'], $e->modules());
            $this->assertStringContainsString('all of the required modules', $e->getMessage());
        }

        try {
            Puppr::authorizeAny($user, 'reports');
            $this->fail('Expected ModuleAccessDeniedException');
        } catch (ModuleAccessDeniedException $e) {
            $this->assertSame('You do not have access to the [reports] module.', $e->getMessage());
        }
    }

    public function test_bypass_callbacks_can_grant_access(): void
    {
        Puppr::create('Billing');
        $admin = $this->createUser('Admin');
        $user = $this->createUser('User');

        Puppr::bypassUsing(fn ($accessor) => $accessor?->name === 'Admin');

        $this->assertTrue(Puppr::allows($admin, 'billing'));
        $this->assertFalse(Puppr::allows($user, 'billing'));

        Puppr::clearBypasses();
        $this->assertFalse(Puppr::allows($admin, 'billing'));
    }

    public function test_bypass_callbacks_do_not_unlock_inactive_modules(): void
    {
        Puppr::create('Billing', ['active' => false]);
        $admin = $this->createUser('Admin');

        Puppr::bypassUsing(fn () => true);

        $this->assertFalse(Puppr::allows($admin, 'billing'));
    }

    public function test_models_without_the_trait_are_rejected(): void
    {
        Puppr::create('Billing');
        $model = PlainModel::create(['name' => 'Acme']);

        $this->expectException(LogicException::class);

        Puppr::grant($model, 'billing');
    }

    public function test_it_is_resolvable_from_the_container_alias(): void
    {
        $this->assertInstanceOf(\Trench94\Puppr\Puppr::class, $this->app->make('puppr'));
        $this->assertSame($this->app->make('puppr'), $this->app->make(\Trench94\Puppr\Puppr::class));
    }
}
