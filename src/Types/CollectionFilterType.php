<?php

namespace Bakery\Types;

use Bakery\Concerns\ModelAware;
use Bakery\Support\Facades\Bakery;
use Bakery\Types\Definitions\Type;
use Illuminate\Support\Collection;
use Bakery\Types\Definitions\ScalarType;

class CollectionFilterType extends InputType
{
    use ModelAware;

    /**
     * Get the name of the Collection Filter Type.
     *
     * @return string
     */
    public function name(): string
    {
        return $this->schema->typename().'Filter';
    }

    /**
     * Return the fields for the collection filter type.
     *
     * @return array
     */
    public function fields(): array
    {
        $fields = collect();

        foreach ($this->schema->getFields() as $name => $type) {
            $fields = $fields->merge($this->getFilters($name, $type));
        }

        foreach ($this->schema->getRelationFields() as $relation => $field) {
            $fields->put($relation, Bakery::resolve($field->name().'Filter'));
        }

        $fields->put('AND', Bakery::resolve($this->name())->list());
        $fields->put('OR', Bakery::resolve($this->name())->list());

        return $fields->map(function (Type $field) {
            return $field->nullable();
        })->toArray();
    }

    /**
     * Return the filters for a field.
     *
     * @param string $name
     * @param string $type
     * @return array
     */
    public function getFilters(string $name, Type $field): Collection
    {
        $fields = collect();

        $type = $field->getType();

        $fields->put($name, new ScalarType($type));
        $fields->put($name.'_contains', new ScalarType($type));
        $fields->put($name.'_not_contains', new ScalarType($type));
        $fields->put($name.'_starts_with', new ScalarType($type));
        $fields->put($name.'_not_starts_with', new ScalarType($type));
        $fields->put($name.'_ends_with', new ScalarType($type));
        $fields->put($name.'_not_ends_with', new ScalarType($type));
        $fields->put($name.'_not', new ScalarType($type));
        $fields->put($name.'_not_in', (new ScalarType($type))->list());
        $fields->put($name.'_in', (new ScalarType($type))->list());
        $fields->put($name.'_lt', new ScalarType($type));
        $fields->put($name.'_lte', new ScalarType($type));
        $fields->put($name.'_gt', new ScalarType($type));
        $fields->put($name.'_gte', new ScalarType($type));

        return $fields;
    }
}
