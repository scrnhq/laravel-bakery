<?php

namespace Bakery\Types;

use Bakery\Fields\Field;
use Bakery\Fields\EloquentField;
use Bakery\Types\Definitions\EloquentInputType;

class CollectionOrderByType extends EloquentInputType
{
    /**
     * Get the name of the Collection Order By BakeField.
     *
     * @return string
     */
    public function name(): string
    {
        return $this->modelSchema->typename().'OrderBy';
    }

    /**
     * Return the fields for the collection order by type.
     *
     * @return array
     */
    public function fields(): array
    {
        $fields = collect();

        foreach ($this->modelSchema->getFields() as $name => $field) {
            $fields->put($name, $this->registry->field('Order')->nullable());
        }

        $this->modelSchema->getRelationFields()->filter(function (Field $field) {
            return $field instanceof EloquentField;
        })->each(function (EloquentField $field, $relation) use ($fields) {
            $fields->put($relation, $this->registry->field($field->name().'OrderBy')->nullable());
        });

        return $fields->toArray();
    }
}
