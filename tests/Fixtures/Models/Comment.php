<?php

namespace Bakery\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    protected $keyType = 'string';

    public function commentable()
    {
        return $this->morphTo();
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
