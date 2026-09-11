<?php

namespace Trench94\Puppr\Tests\Feature;

use Illuminate\Support\Facades\File;
use Trench94\Puppr\PupprFacade as Puppr;
use Trench94\Puppr\Tests\Fixtures\Team;
use Trench94\Puppr\Tests\TestCase;

class CommandsTest extends TestCase
{
    protected function tearDown(): void
    {
        File::delete(config_path('puppr.php'));
        File::delete(File::glob(database_path('migrations/*_create_puppr_tables.php')));

        parent::tearDown();
    }

    public function test_make_creates_a_module(): void
    {
        $this->artisan('puppr:make', ['name' => 'Advanced Reports', '--description' => 'Charts'])
            ->expectsOutputToContain('Module [Advanced Reports] created')
            ->assertSuccessful();

        $module = Puppr::findOrFail('advanced-reports');
        $this->assertSame('Charts', $module->description);
        $this->assertTrue($module->isActive());
    }

    public function test_make_can_create_an_inactive_module_and_rejects_duplicates(): void
    {
        $this->artisan('puppr:make', ['name' => 'Billing', '--inactive' => true])->assertSuccessful();
        $this->assertFalse(Puppr::isActive('billing'));

        $this->artisan('puppr:make', ['name' => 'billing'])
            ->expectsOutputToContain('already exists')
            ->assertFailed();
    }

    public function test_list_shows_modules(): void
    {
        $this->artisan('puppr:list')->expectsOutputToContain('No modules found')->assertSuccessful();

        Puppr::create('Billing');
        Puppr::create('Reports', ['active' => false]);

        $this->artisan('puppr:list')
            ->expectsTable(
                ['ID', 'Name', 'Slug', 'Active', 'Description'],
                [[1, 'Billing', 'billing', 'yes', ''], [2, 'Reports', 'reports', 'no', '']]
            )
            ->assertSuccessful();

        $this->artisan('puppr:list', ['--active' => true])
            ->expectsTable(['ID', 'Name', 'Slug', 'Active', 'Description'], [[1, 'Billing', 'billing', 'yes', '']])
            ->assertSuccessful();
    }

    public function test_activate_and_deactivate(): void
    {
        Puppr::create('Billing');

        $this->artisan('puppr:deactivate', ['module' => 'billing'])->assertSuccessful();
        $this->assertFalse(Puppr::isActive('billing'));

        $this->artisan('puppr:activate', ['module' => 'Billing'])->assertSuccessful();
        $this->assertTrue(Puppr::isActive('billing'));

        $this->artisan('puppr:activate', ['module' => 'nope'])->assertFailed();
        $this->artisan('puppr:deactivate', ['module' => 'nope'])->assertFailed();
    }

    public function test_grant_and_revoke_for_the_default_user_model(): void
    {
        Puppr::create('Billing');
        $user = $this->createUser();

        $this->artisan('puppr:grant', ['module' => 'billing', 'id' => $user->id])
            ->expectsOutputToContain('Granted module [billing] to User #1')
            ->assertSuccessful();
        $this->assertTrue($user->hasModule('billing'));

        $this->artisan('puppr:revoke', ['module' => 'billing', 'id' => $user->id])
            ->expectsOutputToContain('Revoked module [billing] from User #1')
            ->assertSuccessful();
        $this->assertFalse($user->hasModule('billing'));
    }

    public function test_grant_and_revoke_for_a_custom_model(): void
    {
        Puppr::create('Billing');
        $team = $this->createTeam();

        $this->artisan('puppr:grant', ['module' => 'billing', 'id' => $team->id, '--model' => Team::class])
            ->assertSuccessful();
        $this->assertTrue($team->hasModule('billing'));

        $this->artisan('puppr:revoke', ['module' => 'billing', 'id' => $team->id, '--model' => Team::class])
            ->assertSuccessful();
        $this->assertFalse($team->hasModule('billing'));
    }

    public function test_grant_fails_gracefully(): void
    {
        Puppr::create('Billing');

        $this->artisan('puppr:grant', ['module' => 'billing', 'id' => 999])
            ->expectsOutputToContain('was not found')
            ->assertFailed();

        $this->artisan('puppr:grant', ['module' => 'nope', 'id' => $this->createUser()->id])
            ->expectsOutputToContain('does not exist')
            ->assertFailed();

        $this->artisan('puppr:grant', ['module' => 'billing', 'id' => 1, '--model' => 'Not\\A\\Model'])
            ->expectsOutputToContain('is not an Eloquent model')
            ->assertFailed();
    }

    public function test_install_publishes_config_and_migrations(): void
    {
        $this->artisan('puppr:install')
            ->expectsOutputToContain('Configuration published')
            ->expectsOutputToContain('Migrations published')
            ->expectsOutputToContain('Puppr installed')
            ->assertSuccessful();

        $this->assertFileExists(config_path('puppr.php'));
        $this->assertCount(1, File::glob(database_path('migrations/*_create_puppr_tables.php')));
    }

    public function test_install_does_not_overwrite_config_without_consent(): void
    {
        File::ensureDirectoryExists(config_path());
        File::put(config_path('puppr.php'), '<?php return ["custom" => true];');

        $this->artisan('puppr:install')
            ->expectsConfirmation('config/puppr.php already exists. Overwrite it?', 'no')
            ->expectsOutputToContain('Existing configuration kept')
            ->assertSuccessful();

        $this->assertStringContainsString('custom', File::get(config_path('puppr.php')));

        $this->artisan('puppr:install', ['--force' => true])
            ->expectsOutputToContain('Configuration overwritten')
            ->assertSuccessful();

        $this->assertStringNotContainsString('custom', File::get(config_path('puppr.php')));
    }
}
