<?php

namespace Trench94\Puppr\Exceptions;

use InvalidArgumentException;

class ModuleNotFoundException extends InvalidArgumentException
{
    public static function named(string $module): self
    {
        return new self("Module [{$module}] does not exist. Create it with Puppr::create('{$module}') or `php artisan puppr:make {$module}`.");
    }
}
