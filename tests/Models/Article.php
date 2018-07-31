<?php

namespace Bakery\Tests\Models;

use Bakery\Eloquent\Mutable;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use Mutable;

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

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }
}
