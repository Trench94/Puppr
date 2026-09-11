<?php

namespace Trench94\Puppr\Tests\Unit;

use Trench94\Puppr\Models\Module;
use Trench94\Puppr\Tests\Fixtures\User;
use Trench94\Puppr\Tests\TestCase;

class ModuleTest extends TestCase
{
    public function test_it_generates_a_slug_from_the_name(): void
    {
        $module = Module::create(['name' => 'Advanced Reports']);

        $this->assertSame('advanced-reports', $module->slug);
        $this->assertTrue($module->isActive());
    }

    public function test_it_keeps_an_explicit_slug(): void
    {
        $module = Module::create(['name' => 'Advanced Reports', 'slug' => 'reports']);

        $this->assertSame('reports', $module->slug);
    }

    public function test_it_can_be_activated_and_deactivated(): void
    {
        $module = Module::create(['name' => 'Billing']);

        $module->deactivate();
        $this->assertFalse($module->fresh()->isActive());

        $module->activate();
        $this->assertTrue($module->fresh()->isActive());
    }

    public function test_it_has_active_and_inactive_scopes(): void
    {
        Module::create(['name' => 'Billing']);
        Module::create(['name' => 'Reports', 'active' => false]);

        $this->assertSame(['billing'], Module::active()->pluck('slug')->all());
        $this->assertSame(['reports'], Module::inactive()->pluck('slug')->all());
    }

    public function test_named_scope_matches_slug_or_name(): void
    {
        Module::create(['name' => 'Advanced Reports']);

        $this->assertNotNull(Module::named('advanced-reports')->first());
        $this->assertNotNull(Module::named('Advanced Reports')->first());
        $this->assertNull(Module::named('billing')->first());
    }

    public function test_it_exposes_the_accessors_that_were_granted_the_module(): void
    {
        $module = Module::create(['name' => 'Billing']);
        $user = $this->createUser();
        $this->createUser('Other');

        $user->grantModule($module);

        $this->assertSame([$user->id], $module->accessors(User::class)->pluck('id')->all());
    }

    public function test_it_uses_the_configured_table_name(): void
    {
        $this->assertSame('modules', (new Module)->getTable());
    }
}
