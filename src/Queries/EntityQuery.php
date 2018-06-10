<?php

namespace Bakery\Queries;

use Bakery\Concerns\ModelAware;
use Bakery\Traits\JoinsRelationships;
use Illuminate\Database\Eloquent\Builder;

class EntityQuery extends Query
{
    use ModelAware;
    use JoinsRelationships;

    /**
     * Scope the query.
     * This can be overwritten to make your own collection queries.
     *
     * @param Builder $query
     * @param array $args
     * @param $viewer
     * @return Builder
     */
    protected function scopeQuery(Builder $query, array $args, $viewer): Builder
    {
        return $query;
    }
}
