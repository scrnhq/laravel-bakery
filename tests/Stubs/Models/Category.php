<?php

namespace Bakery\Tests\Stubs\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $casts = [
        'id' => 'string',
    ];

    protected $fillable = [
        'name',
    ];
}
