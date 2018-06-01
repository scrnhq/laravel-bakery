<?php

namespace Bakery\Eloquent\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait InteractsWithQueries
{
    /**
     * Boot the query builder on the underlying model.
     *
     * @return Builder
     */
    private function bootQuery(): Builder
    {
        return $this->getModel()->query();
    }

    /**
     * Scope the query.
     *
     * When extending the BakeryModel you can override this method to have
     * some default scoping when querying with Bakery.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeQuery(Builder $query): Builder
    {
        return $query;
    }

    /**
     * Get the query.
     *
     * @return Builder
     */
    final public function query(): Builder
    {
        return $this->scopeQuery($this->bootQuery());
    }
}
