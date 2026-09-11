<?php

namespace Trench94\Puppr\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Trench94\Puppr\Concerns\HasModules;

class Team extends Model
{
    use HasModules;

    protected $table = 'teams';

    protected $guarded = [];
}
