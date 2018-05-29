<?php

namespace Bakery\Types;

use Bakery\Utils\Utils;
use GraphQL\Type\Definition\Type;
use Illuminate\Database\Eloquent\Relations\Relation;

class CreateInputType extends MutationInputType
{
    /**
     * Get the name of the Create Input Type.
     *
     * @return string
     */
    protected function name(): string
    {
        return 'Create'.Utils::typename($this->model->getModel()).'Input';
    }

    /**
     * Return the fields for the Create Input Type.
     *
     * @return array
     */
    public function fields(): array
    {
        $fields = array_merge(
            $this->model->getFillableFields()->toArray(),
            $this->getRelationFields()
        );

        Utils::invariant(
            count($fields) > 0,
            'There are no fields defined for '.class_basename($this->model)
        );

        return $fields;
    }

    /**
     * Generate the input type name for a relationship.
     *
     * @param Relation $relationship
     * @return string
     */
    protected function inputTypeName(Relation $relationship): string
    {
        return 'Create'.class_basename($relationship->getRelated()).'Input';
    }
}
