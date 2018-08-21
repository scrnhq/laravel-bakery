<?php

namespace Bakery\Tests\Models;

use Bakery\Eloquent\Mutable;
use Bakery\Contracts\Mutable as MutableContract;
use Illuminate\Database\Eloquent\Relations\Pivot;

class UserRole extends Pivot implements MutableContract
{
    use Mutable;

    public $fillable = [
        'comment',
    ];
}
