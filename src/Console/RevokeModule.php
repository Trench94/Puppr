<?php

namespace Trench94\Puppr\Console;

use Illuminate\Console\Command;
use Trench94\Puppr\Console\Concerns\ResolvesAccessors;
use Trench94\Puppr\Exceptions\ModuleNotFoundException;
use Trench94\Puppr\Puppr;

class RevokeModule extends Command
{
    use ResolvesAccessors;

    protected $signature = 'puppr:revoke
                            {module : The module name or slug}
                            {id : The id of the user (or other model) to revoke access from}
                            {--model= : The accessor model class (defaults to puppr.user_model)}';

    protected $description = 'Revoke a module from a user or other model';

    public function handle(Puppr $puppr): int
    {
        $accessor = $this->resolveAccessor();

        if ($accessor === null) {
            return self::FAILURE;
        }

        try {
            $module = $puppr->findOrFail((string) $this->argument('module'));
            $puppr->revoke($accessor, $module);
        } catch (ModuleNotFoundException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Revoked module [%s] from %s #%s.',
            $module->slug,
            class_basename($accessor),
            $accessor->getKey()
        ));

        return self::SUCCESS;
    }
}
