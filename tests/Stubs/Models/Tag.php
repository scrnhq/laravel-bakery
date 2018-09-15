<?php

namespace Bakery\Tests\Stubs\Models;

use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    protected $casts = [
        'id' => 'string',
    ];

    protected $fillable = [
        'name',
        'articles',
    ];

    public function articles()
    {
        return $this->morphedByMany(Article::class, 'taggable');
    }
}
