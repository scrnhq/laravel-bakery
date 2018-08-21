<?php

namespace Bakery\Tests\Models;

use Bakery\Eloquent\Mutable;
use Illuminate\Database\Eloquent\Model;
use Bakery\Contracts\Mutable as MutableContract;

class Comment extends Model implements MutableContract
{
    use Mutable;

    protected $casts = [
        'id' => 'string',
    ];

    protected $fillable = [
        'body',
        'user',
        'article',
        'upvotes',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function article()
    {
        return $this->belongsTo(Article::class);
    }

    public function upvotes()
    {
        return $this->morphMany(Upvote::class, 'upvoteable');
    }
}
