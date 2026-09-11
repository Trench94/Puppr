<?php

namespace Trench94\Puppr\Console;

use Illuminate\Console\Command;
use Trench94\Puppr\Puppr;

class ListModules extends Command
{
    protected $signature = 'puppr:list {--active : Only show active modules}';

    protected $description = 'List all Puppr modules';

    public function handle(Puppr $puppr): int
    {
        $modules = $this->option('active') ? $puppr->active() : $puppr->all();

        if ($modules->isEmpty()) {
            $this->warn('No modules found. Create one with `php artisan puppr:make <name>`.');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Name', 'Slug', 'Active', 'Description'],
            $modules->map(fn ($module) => [
                $module->getKey(),
                $module->name,
                $module->slug,
                $module->isActive() ? 'yes' : 'no',
                (string) $module->description,
            ])->all()
        );

        return self::SUCCESS;
    }
}
