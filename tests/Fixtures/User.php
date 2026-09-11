<?php

namespace Trench94\Puppr\Tests\Fixtures;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Trench94\Puppr\Concerns\HasModules;

class User extends Authenticatable
{
    use HasModules;

    protected $table = 'users';

    protected $guarded = [];
}
