<?php

namespace Bakery\Tests\Stubs\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class UserRole extends Pivot
{
    protected $primaryKey = null;

    public $fillable = [
        'comment',
    ];
}
