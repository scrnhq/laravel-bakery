<?php

namespace Bakery\Types\Concerns;

use Illuminate\Support\Str;
use Bakery\Support\Facades\Bakery;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

trait InteractsWithPivot
{
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
    protected function getPivotDefinition()
    {
        $class = $this->pivotRelation->getPivotClass();

        return Bakery::definition($class);
    }

    /**
     * Guess the inverse of a pivot relation.
     *
     * @return BelongsToMany
     */
    protected function guessInverseRelation(): BelongsToMany
    {
        $parent = $this->pivotRelation->getParent();
        $name = Str::camel(Str::plural(class_basename($parent)));

        return $this->pivotRelation->getRelated()->{$name}();
    }
}
