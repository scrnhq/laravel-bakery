<?php

namespace Bakery\Tests\Models;

use Bakery\Eloquent\Mutable;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Bakery\Contracts\Mutable as MutableContract;

class UserRole extends Pivot implements MutableContract
{
    use Mutable;

    public $fillable = [
        'comment',
    ];
}
