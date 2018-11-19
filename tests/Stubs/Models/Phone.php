<?php

namespace Bakery\Tests\Stubs\Models;

use Illuminate\Database\Eloquent\Model;

class Phone extends Model
{
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
