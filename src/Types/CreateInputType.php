<?php

namespace Bakery\Types;

use Bakery\Utils\Utils;
use Bakery\Types\Definitions\Type;
use Illuminate\Support\Collection;

class CreateInputType extends MutationInputType
{
    /**
     * Get the name of the Create Input Type.
     *
     * @return string
     */
    public function name(): string
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
     * @return Collection
     */
    protected function getFillableFields(): Collection
    {
        $fields = parent::getFillableFields();
        $defaults = $this->model->getAttributes();

        return $fields->map(function (Type $field, $key) use ($defaults) {
            if (in_array($key, array_keys($defaults))) {
                return $field->nullable();
            }

            return $field;
        });
    }
}
