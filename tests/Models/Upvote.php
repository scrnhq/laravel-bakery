<?php

namespace Bakery\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations;

class Upvote extends Model
{
    /**
     * Get all of the owning models.
     */
    public function upvoteable(): Relations\MorphTo
    {
        return $this->morphTo();
    }
}
