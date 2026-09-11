<?php

namespace Trench94\Puppr\Console;

use Illuminate\Console\Command;
use Trench94\Puppr\Puppr;

class MakeModule extends Command
{
    protected $signature = 'puppr:make
                            {name : The module name, e.g. "Billing"}
                            {--description= : A short description of the module}
                            {--inactive : Create the module in an inactive state}';

    protected $description = 'Create a new Puppr module';

    public function handle(Puppr $puppr): int
    {
        $name = (string) $this->argument('name');

        if ($existing = $puppr->find($name)) {
            $this->warn("Module [{$existing->slug}] already exists.");

            return self::FAILURE;
        }

        $module = $puppr->create($name, [
            'description' => $this->option('description'),
            'active' => ! $this->option('inactive'),
        ]);

        $this->info(sprintf(
            'Module [%s] created (slug: %s, %s).',
            $module->name,
            $module->slug,
            $module->isActive() ? 'active' : 'inactive'
        ));

        return self::SUCCESS;
    }
}
