<?php

namespace Bakery\Tests\Models;

use Bakery\Eloquent\Mutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations;

class Upvote extends Model
{
    use Mutable;

    protected $fillable = [
        'upvoteable',
    ];

    /**
     * Get all of the owning models.
     */
    public function upvoteable(): Relations\MorphTo
    {
        return $this->morphTo();
    }
}
