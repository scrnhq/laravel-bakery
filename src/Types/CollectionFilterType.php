<?php

namespace Bakery\Types;

use Bakery\Utils\Utils;
use GraphQL\Type\Definition\Type;
use Bakery\Support\Facades\Bakery;

class CollectionFilterType extends ModelAwareInputType
{
    /**
     * Get the name of the Collection Filter Type.
     *
     * @return string
     */
    protected function name(): string
    {
        return Utils::typename($this->model->getModel()).'Filter';
    }

    /**
     * Return the fields for the collection filter type.
     *
     * @return array
     */
    public function fields(): array
    {
        $fields = [];

        foreach ($this->model->getFields() as $name => $type) {
            $fields = array_merge($fields, $this->getFilters($name, $type));
        }

        foreach ($this->model->relations() as $relation => $field) {
            $field = Utils::toFieldArray($field);
            $type = Type::getNamedType($field['type']);

            $fields[$relation] = Bakery::type($type->name.'Filter');
        }

        $fields['AND'] = Bakery::listOf(Bakery::type($this->name));
        $fields['OR'] = Bakery::listOf(Bakery::type($this->name));

        return $fields;
    }

    /**
     * Return the filters for a field.
     *
     * @param string $name
     * @param string $type
     * @return array
     */
    public function getFilters(string $name, $type): array
    {
        if (is_array($type)) {
            $type = Type::getNamedType($type['type']);
        } else {
            $type = Type::getNamedType($type);
        }

        $fields = [];

        if (! Type::isLeafType($type)) {
            return $fields;
        }

        $fields[$name] = $type;
        $fields[$name.'_contains'] = $type;
        $fields[$name.'_not_contains'] = $type;
        $fields[$name.'_starts_with'] = $type;
        $fields[$name.'_not_starts_with'] = $type;
        $fields[$name.'_ends_with'] = $type;
        $fields[$name.'_not_ends_with'] = $type;
        $fields[$name.'_not'] = $type;
        $fields[$name.'_not_in'] = Bakery::listOf($type);
        $fields[$name.'_in'] = Bakery::listOf($type);
        $fields[$name.'_lt'] = $type;
        $fields[$name.'_lte'] = $type;
        $fields[$name.'_gt'] = $type;
        $fields[$name.'_gte'] = $type;

        return $fields;
    }
}
