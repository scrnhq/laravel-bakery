<?php

namespace Bakery\Types;

use Bakery\Utils\Utils;
use GraphQL\Type\Definition\Type;
use Bakery\Support\Facades\Bakery;
use GraphQL\Type\Definition\NonNull;
use Illuminate\Database\Eloquent\Model;

class UpdateInputType extends MutationInputType
{
    /**
     * Get the name of the Update Input Type.
     *
     * @return string
     */
    protected function name(): string
    {
        return 'Update'.$this->schema->typename().'Input';
    }

    /**
     * Return the fields for theUpdate Input Type.
     *
     * @return array
     */
    public function fields(): array
    {
        $fields = array_merge(
            $this->getFillableFields(),
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
     * Updating in Bakery works like PATCH you only have to pass in
     * the values you want to update. The rest stays untouched.
     * Because of that we have to remove the nonNull wrappers on the fields.
     *
     * @return array
     */
    private function getFillableFields(): array
    {
        return Utils::nullifyFields($this->schema->getFillableFields())->toArray();
    }

    /**
     * Generate the input type name for a relationship.
     *
     * @param string $relation
     * @return string
     */
    protected function inputTypename(string $relation): string
    {
        return 'Update'.Utils::typename($relation).'Input';
    }
}
