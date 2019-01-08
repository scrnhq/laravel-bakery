<?php

namespace Bakery\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    public function users()
    {
        return $this->belongsToMany(User::class)
            ->as($_SERVER['eloquent.role.users.pivot'] ?? 'pivot')
            ->withPivot(['admin'])
            ->using(UserRole::class);
    }
}
