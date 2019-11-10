<?php

namespace Bakery\Types;

use Bakery\Fields\EloquentField;
use Bakery\Fields\Field;
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
        $fields = $this->modelSchema->getSearchableFields()->map(function (Field $field) {
            return $this->registry->field($this->registry->boolean())->nullable();
        });

        $relations = $this->modelSchema->getSearchableRelationFields()->map(function (EloquentField $field) {
            $searchTypeName = $field->getName().'Search';

            return $this->registry->field($searchTypeName)->nullable();
        });

        return $fields->merge($relations)->toArray();
    }
}
