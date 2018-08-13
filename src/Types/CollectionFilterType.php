<?php

namespace Bakery\Types;

use Bakery\Concerns\ModelAware;
use Bakery\Support\Facades\Bakery;
use Bakery\Types\Definitions\Type;
use Illuminate\Support\Collection;
use Bakery\Types\Definitions\InputType;
use Bakery\Types\Definitions\EloquentType;

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
            if ($type->isLeafType()) {
                $fields = $fields->merge($this->getFilters($name, $type));
            }
        }

        $this->schema->getRelationFields()->filter(function (Type $field) {
            return $field instanceof EloquentType;
        })->each(function (EloquentType $field, $relation) use ($fields) {
            $fields->put($relation, Bakery::type($field->name().'Filter'));
        });

        $fields->put('AND', Bakery::type($this->name())->list());
        $fields->put('OR', Bakery::type($this->name())->list());

        return $fields->map(function (Type $field) {
            return $field->nullable();
        })->toArray();
    }

    /**
     * Return the filters for a field.
     *
     * @param string $name
     * @param Type $field
     * @return Collection
     */
    public function getFilters(string $name, Type $field): Collection
    {
        $fields = collect();

        $type = $field->getType();

        $fields->put($name, new Type($type));
        $fields->put($name.'_contains', new Type($type));
        $fields->put($name.'_not_contains', new Type($type));
        $fields->put($name.'_starts_with', new Type($type));
        $fields->put($name.'_not_starts_with', new Type($type));
        $fields->put($name.'_ends_with', new Type($type));
        $fields->put($name.'_not_ends_with', new Type($type));
        $fields->put($name.'_not', new Type($type));
        $fields->put($name.'_not_in', (new Type($type))->list());
        $fields->put($name.'_in', (new Type($type))->list());
        $fields->put($name.'_lt', new Type($type));
        $fields->put($name.'_lte', new Type($type));
        $fields->put($name.'_gt', new Type($type));
        $fields->put($name.'_gte', new Type($type));

        return $fields;
    }
}
