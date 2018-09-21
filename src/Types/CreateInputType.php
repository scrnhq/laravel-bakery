<?php

namespace Bakery\Types;

use Bakery\Fields\Field;
use Bakery\Utils\Utils;
use Bakery\Types\Definitions\Type;
use Illuminate\Support\Collection;

class CreateInputType extends EloquentMutationInputType
{
    /**
     * Get the name of the Create Input BakeField.
     *
     * @return string
     */
    public function name(): string
    {
        return 'Create'.$this->modelSchema->typename().'Input';
    }

    /**
     * Return the fields for the Create Input BakeField.
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
            'There are no fillable fields defined for '.class_basename($this->model).'. '.
            'Make sure that a mutable model has at least one fillable field.'
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

        return $fields->map(function (Field $field, $key) use ($defaults) {
            if (in_array($key, array_keys($defaults))) {
                return $field->nullable();
            }

            return $field;
        });
    }
}
