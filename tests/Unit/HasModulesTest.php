<?php

namespace Trench94\Puppr\Tests\Unit;

use Trench94\Puppr\Exceptions\ModuleNotFoundException;
use Trench94\Puppr\Tests\TestCase;

class HasModulesTest extends TestCase
{
    public function test_a_module_can_be_granted_by_name_slug_instance_or_id(): void
    {
        $module = $this->createModule('Advanced Reports');
        $user = $this->createUser();

        $user->grantModule('Advanced Reports');
        $this->assertTrue($user->hasModule('advanced-reports'));

        $user->revokeModule($module);
        $this->assertFalse($user->hasModule('advanced-reports'));

        $user->grantModule($module->id);
        $this->assertTrue($user->hasModule($module));
    }

    public function test_granting_twice_does_not_duplicate_the_pivot_row(): void
    {
        $this->createModule('Billing');
        $user = $this->createUser();

        $user->grantModule('billing')->grantModule('billing');

        $this->assertCount(1, $user->modules);
    }

    public function test_multiple_modules_can_be_granted_at_once(): void
    {
        $this->createModule('Billing');
        $this->createModule('Reports');
        $user = $this->createUser();

        $user->grantModule('billing', 'reports');
        $this->assertTrue($user->hasAllModules('billing', 'reports'));

        $user->revokeAllModules();
        $this->assertCount(0, $user->modules);

        $user->grantModule(['billing', 'reports']);
        $this->assertTrue($user->hasAllModules(['billing', 'reports']));
    }

    public function test_granting_an_unknown_module_throws(): void
    {
        $user = $this->createUser();

        $this->expectException(ModuleNotFoundException::class);

        $user->grantModule('does-not-exist');
    }

    public function test_revoking_an_unknown_module_is_ignored(): void
    {
        $this->createModule('Billing');
        $user = $this->createUser();
        $user->grantModule('billing');

        $user->revokeModule('does-not-exist');

        $this->assertTrue($user->hasModule('billing'));
    }

    public function test_sync_replaces_the_granted_modules(): void
    {
        $this->createModule('Billing');
        $this->createModule('Reports');
        $this->createModule('Chat');
        $user = $this->createUser();

        $user->grantModule('billing', 'reports');
        $user->syncModules('chat');

        $this->assertSame(['chat'], $user->modules->pluck('slug')->all());
    }

    public function test_has_module_requires_the_module_to_be_active(): void
    {
        $module = $this->createModule('Billing');
        $user = $this->createUser();
        $user->grantModule($module);

        $module->deactivate();

        $this->assertFalse($user->hasModule('billing'));
        $this->assertTrue($user->isGrantedModule('billing'));
        $this->assertSame([], $user->activeModules()->pluck('slug')->all());
    }

    public function test_has_any_module(): void
    {
        $this->createModule('Billing');
        $this->createModule('Reports');
        $user = $this->createUser();
        $user->grantModule('reports');

        $this->assertTrue($user->hasAnyModule('billing', 'reports'));
        $this->assertFalse($user->hasAllModules('billing', 'reports'));
        $this->assertFalse($user->hasAnyModule('billing', 'missing'));
    }

    public function test_it_uses_the_loaded_relation_when_available(): void
    {
        $this->createModule('Billing');
        $user = $this->createUser();
        $user->grantModule('billing');

        $user = $user->fresh()->load('modules');

        $this->assertTrue($user->hasModule('billing'));
        $this->assertCount(1, $user->activeModules());
    }

    public function test_any_model_can_be_granted_modules(): void
    {
        $this->createModule('Billing');
        $team = $this->createTeam();
        $user = $this->createUser();

        $team->grantModule('billing');

        $this->assertTrue($team->hasModule('billing'));
        $this->assertFalse($user->hasModule('billing'));
    }

    public function test_deleting_a_module_removes_its_grants(): void
    {
        $module = $this->createModule('Billing');
        $user = $this->createUser();
        $user->grantModule($module);

        $module->delete();

        $this->assertSame(0, $this->app['db']->table('module_access')->count());
        $this->assertFalse($user->fresh()->hasModule('billing'));
    }
}
