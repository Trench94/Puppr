<?php

namespace Trench94\Puppr\Console\Concerns;

use Illuminate\Database\Eloquent\Model;

trait ResolvesAccessors
{
    /**
     * Resolve the accessor model targeted by the command.
     */
    protected function resolveAccessor(): ?Model
    {
        $class = (string) ($this->option('model') ?: config('puppr.user_model', 'App\\Models\\User'));

        if (! class_exists($class) || ! is_subclass_of($class, Model::class)) {
            $this->error("[{$class}] is not an Eloquent model. Pass --model=App\\\\Models\\\\Team or set puppr.user_model.");

            return null;
        }

        $accessor = $class::query()->find($this->argument('id'));

        if ($accessor === null) {
            $this->error(sprintf('%s with id [%s] was not found.', class_basename($class), $this->argument('id')));

            return null;
        }

        return $accessor;
    }
}
