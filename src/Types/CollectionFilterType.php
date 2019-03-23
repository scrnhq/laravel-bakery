<?php

namespace Bakery\Types;

use Bakery\Fields\Field;
use Bakery\Fields\EloquentField;
use Illuminate\Support\Collection;
use Bakery\Fields\PolymorphicField;
use Bakery\Types\Definitions\EloquentInputType;

class CollectionFilterType extends EloquentInputType
{
    /**
     * Get the name of the Collection Filter BakeField.
     *
     * @return string
     */
    public function name(): string
    {
        return $this->modelSchema->typename().'Filter';
    }

    /**
     * Return the fields for the collection filter type.
     *
     * @return array
     */
    public function fields(): array
    {
        $fields = collect()
            ->merge($this->getScalarFilters())
            ->merge($this->getRelationFilters())
            ->put('AND', $this->registry->field($this->registry->type($this->name()))->list())
            ->put('OR', $this->registry->field($this->registry->type($this->name()))->list());

        return $fields->map(function (Field $type) {
            return $type->nullable()->nullableItems();
        })->toArray();
    }

    /**
     * Return the filters for the scalar fields.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getScalarFilters(): Collection
    {
        $fields = $this->modelSchema->getFields();

        return $fields->except(function (Field $field) {
            return $field instanceof PolymorphicField;
        })->keys()->reduce(function (Collection $result, string $name) use ($fields) {
            $field = $fields->get($name);

            return $field->getType()->isLeafType() ? $result->merge($this->getFilters($name, $field)) : $result;
        }, collect());
    }

    /**
     * Return the filters for the relation fields.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getRelationFilters(): Collection
    {
        $fields = $this->modelSchema->getRelationFields();

        return $fields->filter(function (Field $field) {
            return $field instanceof EloquentField;
        })->keys()->reduce(function (Collection $result, string $name) use ($fields) {
            $field = $fields->get($name);

            return $result->put($name, $this->registry->field($this->registry->type($field->name().'Filter')));
        }, collect());
    }

    /**
     * Return the filters for a field.
     *
     * @param string $name
     * @param \Bakery\Fields\Field $field
     * @return \Illuminate\Support\Collection
     */
    protected function getFilters(string $name, Field $field): Collection
    {
        $fields = collect();

        $type = $field->getType();

        $fields->put($name, $this->registry->field($type));
        $fields->put($name.'_contains', $this->registry->field($type));
        $fields->put($name.'_not_contains', $this->registry->field($type));
        $fields->put($name.'_starts_with', $this->registry->field($type));
        $fields->put($name.'_not_starts_with', $this->registry->field($type));
        $fields->put($name.'_ends_with', $this->registry->field($type));
        $fields->put($name.'_not_ends_with', $this->registry->field($type));
        $fields->put($name.'_not', $this->registry->field($type));
        $fields->put($name.'_not_in', $this->registry->field($type)->list());
        $fields->put($name.'_in', $this->registry->field($type)->list());
        $fields->put($name.'_lt', $this->registry->field($type));
        $fields->put($name.'_lte', $this->registry->field($type));
        $fields->put($name.'_gt', $this->registry->field($type));
        $fields->put($name.'_gte', $this->registry->field($type));

        return $fields;
    }
}
