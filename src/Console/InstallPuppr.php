<?php


namespace App\Packages\trench94\puppr\src\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InstallPuppr extends Command
{

    protected $signature = 'puppr:install';

    protected $description = 'Install Puppr into your project';

    public function handle()
    {
        $this->info('Installing Puppr...');

        $this->info('Publishing configuration...');

        if (! $this->configExists('puppr.php')) {
            $this->publishConfiguration();
            $this->info('Published configuration');
        } else {
            if ($this->shouldOverwriteConfig()) {
                $this->info('Overwriting configuration file...');
                $this->publishConfiguration($force = true);
            } else {
                $this->info('Existing configuration was not overwritten');
            }
        }

        $this->info('Installed Puppr');
    }

    private function configExists($fileName)
    {
        return File::exists(config_path($fileName));
    }

    private function shouldOverwriteConfig()
    {
        return $this->confirm(
            'Config file already exists. Do you want to overwrite it?',
            false
        );
    }

    private function publishConfiguration($forcePublish = false)
    {
        $params = [
            '--provider' => "trench94\puppr\PupprServiceProvider",
            '--tag' => "config"
        ];

        if ($forcePublish === true) {
            $params['--force'] = true;
        }

        $this->call('vendor:publish', $params);
    }
}
