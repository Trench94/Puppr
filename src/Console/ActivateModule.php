<?php

namespace Trench94\Puppr\Console;

use Illuminate\Console\Command;
use Trench94\Puppr\Exceptions\ModuleNotFoundException;
use Trench94\Puppr\Puppr;

class ActivateModule extends Command
{
    protected $signature = 'puppr:activate {module : The module name or slug}';

    protected $description = 'Activate a Puppr module';

    public function handle(Puppr $puppr): int
    {
        try {
            $module = $puppr->activate((string) $this->argument('module'));
        } catch (ModuleNotFoundException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info("Module [{$module->slug}] is now active.");

        return self::SUCCESS;
    }
}
