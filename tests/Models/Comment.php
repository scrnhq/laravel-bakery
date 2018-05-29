<?php

namespace Bakery\Tests\Models;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    protected $casts = [
        'id' => 'string',
    ];

    protected $fillable = [
        'body',
        'user',
        'article',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function article()
    {
        return $this->belongsTo(Article::class);
    }
}
