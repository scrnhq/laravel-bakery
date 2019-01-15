<?php

namespace Bakery\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;

class Phone extends Model
{
    protected $keyType = 'string';

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
