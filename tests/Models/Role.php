<?php

namespace Bakery\Tests\Models;

use Bakery\Eloquent\BakeryMutable;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use BakeryMutable;
    
    protected $casts = [
        'id' => 'string',
    ];

    protected $fillable = [
        'name',
    ];
}
