<?php

namespace Bakery\Tests\Models;

use Bakery\Eloquent\BakeryMutable;
use Illuminate\Database\Eloquent\Model;

class Phone extends Model
{
    use BakeryMutable;

    protected $casts = [
        'id' => 'string',
    ];

    protected $fillable = [
        'number',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
