<?php

namespace Trench94\Puppr\Console;

use Illuminate\Console\Command;
use Trench94\Puppr\Exceptions\ModuleNotFoundException;
use Trench94\Puppr\Puppr;

class DeactivateModule extends Command
{
    protected $signature = 'puppr:deactivate {module : The module name or slug}';

    protected $description = 'Deactivate a Puppr module (nobody can use it until it is activated again)';

    public function handle(Puppr $puppr): int
    {
        try {
            $module = $puppr->deactivate((string) $this->argument('module'));
        } catch (ModuleNotFoundException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info("Module [{$module->slug}] is now inactive.");

        return self::SUCCESS;
    }
}
