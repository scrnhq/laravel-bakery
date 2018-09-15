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
    public function getQuery(): Builder
    {
        /** @var \Illuminate\Database\Eloquent\Model $model */
        $model = $this->getModel();

        return $model->newQuery();
    }
}
