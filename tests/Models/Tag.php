<?php

namespace Bakery\Tests\Models;

use Bakery\Eloquent\Mutable;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    use Mutable;

    protected $casts = [
        'id' => 'string',
    ];

    protected $fillable = [
        'name',
    ];

    public function articles()
    {
        return $this->belongsToMany(Article::class);
    }
}
