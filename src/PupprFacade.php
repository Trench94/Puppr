<?php

namespace Trench94\Puppr;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Trench94\Puppr\Skeleton\SkeletonClass
 */
class PupprFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'puppr';
    }
}
