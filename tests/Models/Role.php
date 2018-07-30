<?php

namespace Bakery\Tests\Models;

use Bakery\Eloquent\Mutable;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use Mutable;

    protected $casts = [
        'id' => 'string',
    ];

    protected $fillable = [
        'name',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class)
            ->using(UserRole::class)
            ->withPivot('comment')
            ->withTimestamps();
    }
}
