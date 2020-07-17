<?php

namespace Bakery\Types;

use Bakery\Fields\Field;
use Illuminate\Support\Collection;
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

        foreach ($this->getSortableFields() as $name => $field) {
            $fields->put($name, $this->registry->field('Order')->nullable());
        }

        return $fields->toArray();
    }

    /**
     * Get the fields that are sortable.
     *
     * @return Collection
     */
    protected function getSortableFields(): Collection
    {
        return $this->modelSchema->getFields()->filter(function (Field $field) {
            return $field->isSortable();
        });
    }
}
