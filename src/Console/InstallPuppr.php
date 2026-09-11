<?php

namespace Trench94\Puppr\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Trench94\Puppr\PupprServiceProvider;

class InstallPuppr extends Command
{
    protected $signature = 'puppr:install
                            {--force : Overwrite an existing configuration file}
                            {--migrate : Run the database migrations after publishing}';

    protected $description = 'Install Puppr: publish the configuration and migrations';

    public function handle(): int
    {
        $this->info('Installing Puppr...');

        $this->publishConfiguration();
        $this->publishMigrations();

        if ($this->option('migrate')) {
            $this->call('migrate');
        }

        $this->newLine();
        $this->info('Puppr installed.');

        if (! $this->option('migrate')) {
            $this->line('Next: run <comment>php artisan migrate</comment> to create the module tables.');
        }

        $this->line('Then add the <comment>Trench94\Puppr\Concerns\HasModules</comment> trait to your User model.');

        return self::SUCCESS;
    }

    protected function publishConfiguration(): void
    {
        $exists = File::exists(config_path('puppr.php'));

        if ($exists && ! $this->option('force')) {
            if (! $this->confirm('config/puppr.php already exists. Overwrite it?', false)) {
                $this->line('Existing configuration kept.');

                return;
            }
        }

        $this->callSilently('vendor:publish', [
            '--provider' => PupprServiceProvider::class,
            '--tag' => 'puppr-config',
            '--force' => true,
        ]);

        $this->line($exists ? 'Configuration overwritten.' : 'Configuration published to config/puppr.php.');
    }

    protected function publishMigrations(): void
    {
        if (count(File::glob(database_path('migrations/*_create_puppr_tables.php'))) > 0) {
            $this->line('Migrations already published.');

            return;
        }

        $this->callSilently('vendor:publish', [
            '--provider' => PupprServiceProvider::class,
            '--tag' => 'puppr-migrations',
        ]);

        $this->line('Migrations published.');
    }
}
