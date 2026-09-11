<?php

namespace Trench94\Puppr\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;

/**
 * Thrown when an accessor is not permitted to use a module.
 *
 * Extends Laravel's AuthorizationException so it is rendered as a 403
 * response by the framework's exception handler.
 */
class ModuleAccessDeniedException extends AuthorizationException
{
    /**
     * @var array<int, string>
     */
    protected array $modules = [];

    /**
     * @param  array<int, string>  $modules
     */
    public static function forModules(array $modules, bool $requireAll = true): self
    {
        $list = implode(', ', $modules);

        $message = count($modules) > 1
            ? ($requireAll
                ? "You do not have access to all of the required modules: {$list}."
                : "You do not have access to any of the required modules: {$list}.")
            : "You do not have access to the [{$list}] module.";

        $exception = new self($message);
        $exception->modules = $modules;

        return $exception;
    }

    /**
     * The modules that were checked when access was denied.
     *
     * @return array<int, string>
     */
    public function modules(): array
    {
        return $this->modules;
    }
}
