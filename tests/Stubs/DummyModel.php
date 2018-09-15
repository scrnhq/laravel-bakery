<?php

namespace Bakery\Tests\Stubs;

use Illuminate\Database\Eloquent\Model;

class DummyModel extends Model
{
    protected $fillable = [
        'name',
    ];
}
