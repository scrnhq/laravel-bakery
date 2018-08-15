<?php

namespace Bakery\Tests\Models;

use Bakery\Eloquent\Mutable;
use Illuminate\Database\Eloquent\Model;
use Bakery\Contracts\Mutable as MutableContract;

class Phone extends Model implements MutableContract
{
    use Mutable;

    protected $casts = [
        'id' => 'string',
    ];

    protected $fillable = [
        'number',
        'user',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
