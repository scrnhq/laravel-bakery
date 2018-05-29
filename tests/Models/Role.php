<?php

namespace Bakery\Tests\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $casts = [
        'id' => 'string',
    ];

    protected $fillable = [
        'name',
    ];
}
