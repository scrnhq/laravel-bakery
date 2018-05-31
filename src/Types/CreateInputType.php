<?php

namespace Bakery\Types;

use Bakery\Utils\Utils;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Collection;
use Bakery\Support\Facades\Bakery;
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
        return 'Create'.$this->schema->typename().'Input';
    }

    /**
     * Return the fields for the Create Input Type.
     *
     * @return array
     */
    public function fields(): array
    {
        $fields = array_merge(
            $this->getFillableFields()->toArray(),
            $this->getRelationFields()
        );

        Utils::invariant(
            count($fields) > 0,
            'There are no fields defined for '.class_basename($this->model)
        );

        return $fields;
    }

    /**
     * Get the fillable fields of the model.
     *
     * @return Collection
     */
    protected function getFillableFields(): Collection
    {
        return $this->schema->getFillableFields()->map(function ($field, $key) {
            $defaults = $this->model->getAttributes();

            if (in_array($key, array_keys($defaults))) {
                return Utils::nullifyField($field);
            }

            return $field;
        });
    }

    /**
     * Generate the input type name for a relationship.
     *
     * @param string $relation
     * @return string
     */
    protected function inputTypename(string $relation): string
    {
        return 'Create'.Utils::typename($relation).'Input';
    }
}
