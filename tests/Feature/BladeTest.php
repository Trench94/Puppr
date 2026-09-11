<?php

namespace Trench94\Puppr\Tests\Feature;

use Trench94\Puppr\PupprFacade as Puppr;
use Trench94\Puppr\Tests\TestCase;

class BladeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Puppr::create('Billing');
        Puppr::create('Reports');
    }

    public function test_directives_for_a_user_with_one_module(): void
    {
        $this->actingAs($this->createUser()->grantModule('billing'));

        $html = view('modules')->render();

        $this->assertStringContainsString('has-billing', $html);
        $this->assertStringNotContainsString('no-billing', $html);
        $this->assertStringNotContainsString('has-both', $html);
        $this->assertStringContainsString('missing-reports', $html);
        $this->assertStringContainsString('has-any', $html);
        $this->assertStringContainsString('gate-billing', $html);
    }

    public function test_directives_for_a_user_with_all_modules(): void
    {
        $this->actingAs($this->createUser()->grantModule('billing', 'reports'));

        $html = view('modules')->render();

        $this->assertStringContainsString('has-both', $html);
        $this->assertStringNotContainsString('missing-reports', $html);
    }

    public function test_directives_for_guests(): void
    {
        $html = view('modules')->render();

        $this->assertStringContainsString('no-billing', $html);
        $this->assertStringNotContainsString('has-any', $html);
        $this->assertStringNotContainsString('gate-billing', $html);
    }
}
