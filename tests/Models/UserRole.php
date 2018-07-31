<?php

namespace Bakery\Tests\Models;

use Bakery\Eloquent\Mutable;
use Illuminate\Database\Eloquent\Relations\Pivot;

class UserRole extends Pivot
{
    use Mutable;

    public $fillable = [
        'comment',
    ];
}
