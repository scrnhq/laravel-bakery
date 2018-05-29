<?php

namespace Bakery\Tests\Models;

use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    protected $attributes = [
        'content' => 'Lorem ipsum',
    ];

    protected $casts = [
        'id' => 'string',
    ];

    protected $fillable = [
        'title',
        'content',
        'user',
        'slug',
        'comments',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }
}
