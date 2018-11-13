<?php

namespace Bakery\Types;

use Bakery\Fields\Field;
use Bakery\Types\Definitions\EloquentType;
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
        $fields = collect($this->modelSchema->getFields());

        $fields->each(function (Field $field, $name) use (&$fields) {
            $fields->put($name, $this->registry->field($this->registry->boolean())->nullable());
        });

        foreach ($this->modelSchema->getRelationFields() as $relation => $field) {
            if ($field instanceof EloquentType) {
                $fields[$relation] = $this->registry->field($field->getName().'Search')->nullable();
            }
        }

        return $fields->toArray();
    }
}
