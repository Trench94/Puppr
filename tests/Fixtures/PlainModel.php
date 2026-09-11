<?php

namespace Trench94\Puppr\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;

class PlainModel extends Model
{
    protected $table = 'teams';

    protected $guarded = [];
}
