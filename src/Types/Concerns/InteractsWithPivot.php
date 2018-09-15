<?php

namespace Bakery\Types\Concerns;

use Bakery\Support\Facades\Bakery;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

trait InteractsWithPivot
{
    /**
     * @var \Bakery\Bakery
     */
    protected $bakery;

    /**
     * The pivot relationship.
     *
     * @var BelongsToMany
     */
    protected $pivotRelation;

    /**
     * Set the pivot relationship.
     *
     * @param BelongsToMany $relation
     * @return \Bakery\Types\Concerns\InteractsWithPivot
     */
    public function setPivotRelation(BelongsToMany $relation)
    {
        $this->pivotRelation = $relation;

        return $this;
    }

    /**
     * Get the pivot relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function getPivotRelation(): BelongsToMany
    {
        return $this->pivotRelation;
    }

    /**
     * Get the pivot definition class.
     *
     * @return mixed
     */
    protected function getPivotModelSchema()
    {
        $class = $this->pivotRelation->getPivotClass();

        return $this->bakery->resolveSchemaForModel($class);
    }
}
