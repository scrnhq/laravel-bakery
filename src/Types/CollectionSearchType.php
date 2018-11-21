<?php

namespace Bakery\Types;

use Bakery\Fields\Field;
use Bakery\Fields\EloquentField;
use GraphQL\Type\Definition\StringType;
use Bakery\Types\Definitions\EloquentInputType;

class CollectionSearchType extends EloquentInputType
{
    /**
     * Get the name of the Collection Search BakeField.
     *
     * @return string
     */
    public function name(): string
    {
        return $this->modelSchema->typename().'Search';
    }

    /**
     * Return the fields for the collection filter type.
     *
     * @return array
     */
    public function fields(): array
    {
        $fields = $this->modelSchema->getFields()->filter(function (Field $field) {
            return $field->getType()->getType() instanceof StringType;
        })->map(function (Field $field) {
            return $this->registry->field($this->registry->boolean())->nullable();
        });

        $relations = $this->modelSchema->getRelationFields()->filter(function (Field $field) {
            return $field instanceof EloquentField;
        })->map(function (EloquentField $field) {
            return $this->registry->field($field->getName().'Search')->nullable();
        });

        return $fields->merge($relations)->toArray();
    }
}
