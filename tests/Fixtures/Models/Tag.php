<?php

namespace Bakery\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    protected $keyType = 'string';

    public function articles()
    {
        return $this->morphedByMany(Article::class, 'taggable');
    }
}
