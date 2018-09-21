<?php

namespace Bakery\Types\Concerns;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

trait InteractsWithPivot
{
    /**
     * @var \Bakery\TypeRegistry
     */
    protected $registry;

    /**
     * @var \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    protected $relation;

    /**
     * @var \Bakery\Eloquent\ModelSchema
     */
    protected $pivotModelSchema;

    /**
     * @var string
     */
    private $pivotRelationName;

    /**
     * @var \Illuminate\Database\Eloquent\Model
     */
    private $pivotParent;

    /**
     * Set the pivot relationship.
     *
     * @param BelongsToMany $relation
     * @return \Bakery\Types\Concerns\InteractsWithPivot
     */
    public function setPivotRelation(BelongsToMany $relation)
    {
        $this->relation = $relation;
        $this->pivotParent = $relation->getParent();
        $this->pivotRelationName = $relation->getRelationName();

        return $this;
    }

    /**
     * Get the pivot relation for a model.
     *
     * @return BelongsToMany
     */
    protected function getRelation(): BelongsToMany
    {
        if (isset($this->relation)) {
            return $this->relation;
        }

        return $this->relation = $this->model->{$this->pivotRelationName}();
    }

    /**
     * @return \Bakery\Eloquent\ModelSchema
     */
    public function getPivotModelSchema()
    {
        if (isset($this->pivotModelSchema)) {
            return $this->pivotModelSchema;
        }

        if ($this->registry->hasSchemaForModel($this->relation->getPivotClass())) {
            $this->pivotModelSchema = $this->registry->resolveSchemaForModel($this->relation->getPivotClass());

            return $this->pivotModelSchema;
        }

        return null;
    }

    /**
     * Invoked when the object is serialized.
     *
     * @return array
     */
    public function __sleep()
    {
        $fields = ['pivotParent', 'pivotRelationName'];

        return array_merge($fields, parent::__sleep());
    }
}
