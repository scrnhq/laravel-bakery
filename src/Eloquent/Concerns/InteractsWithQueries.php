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
    public function getBakeryQuery($viewer): Builder
    {
        $model = $this->getModel();
        $query = $model->query();

        if (method_exists($model, 'scopeAuthorizedForReading')) {
            $query = $query->authorizedForReading($viewer);
        }

        return $this->scopeQuery($query);
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
}
