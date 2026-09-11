<?php

namespace Trench94\Puppr\Tests\Feature;

use Illuminate\Support\Facades\Gate;
use Trench94\Puppr\PupprFacade as Puppr;
use Trench94\Puppr\Tests\TestCase;

class GateTest extends TestCase
{
    public function test_the_module_gate_ability_is_registered(): void
    {
        Puppr::create('Billing');
        Puppr::create('Reports');
        $user = $this->createUser()->grantModule('billing');

        $this->assertTrue($user->can('module', 'billing'));
        $this->assertFalse($user->can('module', 'reports'));
        $this->assertFalse($user->can('module', ['billing', 'reports']));
        $this->assertTrue($user->cannot('module', 'reports'));

        $this->assertTrue(Gate::forUser($user)->allows('module', 'billing'));
        $this->assertFalse(Gate::allows('module', 'billing'));
    }
}
