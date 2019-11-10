<?php

namespace Bakery\Types;

use Bakery\Fields\EloquentField;
use Bakery\Fields\Field;
use Bakery\Fields\PolymorphicField;
use Bakery\Types\Definitions\EloquentInputType;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CollectionFilterType extends EloquentInputType
{
    /**
     * @var array
     */
    public static $filters = [
        'Contains',
        'NotContains',
        'StartsWith',
        'NotStartsWith',
        'EndsWith',
        'NotEndsWith',
        'Not',
        'NotIn',
        'In',
        'LessThan',
        'LessThanOrEquals',
        'GreaterThan',
        'GreaterThanOrEquals',
    ];

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

        return $fields->reject(function (Field $field) {
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

        foreach (self::$filters as $filter) {
            if (Str::endsWith($filter, 'In')) {
                $fields->put($name.$filter, $this->registry->field($type)->list());
            } else {
                $fields->put($name.$filter, $this->registry->field($type));
            }
        }

        return $fields;
    }
}
