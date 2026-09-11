<?php

namespace Trench94\Puppr\Console;

use Illuminate\Console\Command;
use Trench94\Puppr\Console\Concerns\ResolvesAccessors;
use Trench94\Puppr\Exceptions\ModuleNotFoundException;
use Trench94\Puppr\Puppr;

class GrantModule extends Command
{
    use ResolvesAccessors;

    protected $signature = 'puppr:grant
                            {module : The module name or slug}
                            {id : The id of the user (or other model) to grant access to}
                            {--model= : The accessor model class (defaults to puppr.user_model)}';

    protected $description = 'Grant a module to a user or other model';

    public function handle(Puppr $puppr): int
    {
        $accessor = $this->resolveAccessor();

        if ($accessor === null) {
            return self::FAILURE;
        }

        try {
            $module = $puppr->findOrFail((string) $this->argument('module'));
            $puppr->grant($accessor, $module);
        } catch (ModuleNotFoundException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Granted module [%s] to %s #%s.',
            $module->slug,
            class_basename($accessor),
            $accessor->getKey()
        ));

        return self::SUCCESS;
    }
}
