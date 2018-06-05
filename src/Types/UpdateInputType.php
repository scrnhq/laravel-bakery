<?php

namespace Bakery\Types;

use Bakery\Utils\Utils;
use Illuminate\Support\Collection;

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
            $this->getFillableFields()->toArray(),
            $this->getRelationFields()->toArray()
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
     * @return Collection
     */
    protected function getFillableFields(): Collection
    {
        $fields = parent::getFillableFields();

        return Utils::nullifyFields($fields);
    }
}
