<?php

namespace Bakery\Tests\Stubs\Models;

use Bakery\Eloquent\Traits\BakeryTransactionalAware;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use BakeryTransactionalAware;

    protected $casts = [
        'id' => 'string',
    ];

    protected $fillable = [
        'title',
        'content',
        'user',
        'slug',
        'comments',
        'category',
        'tags',
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
        return $this->morphToMany(Tag::class, 'taggable');
    }

    public function upvotes()
    {
        return $this->morphMany(Upvote::class, 'upvoteable');
    }
}
