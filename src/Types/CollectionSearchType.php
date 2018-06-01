<?php

namespace Bakery\Types;

use Bakery\Utils\Utils;
use Bakery\Concerns\ModelAware;
use GraphQL\Type\Definition\Type;
use Bakery\Support\Facades\Bakery;
use GraphQL\Type\Definition\IDType;
use GraphQL\Type\Definition\StringType;

class CollectionSearchType extends InputType
{
    use ModelAware;

    /**
     * Get the name of the Collection Search Type.
     *
     * @return string
     */
    protected function name(): string
    {
        return $this->schema->typename().'Search';
    }

    /**
     * Return the fields for the collection filter type.
     *
     * @return array
     */
    public function fields(): array
    {
        $fields = [];

        foreach ($this->schema->getFields() as $name => $field) {
            $field = Utils::toFieldArray($field);
            $type = Type::getNamedType($field['type']);

            if ($type instanceof StringType || $type instanceof IDType) {
                $fields[$name] = Bakery::boolean();
            }
        }

        foreach ($this->model->getRElations() as $relation => $field) {
            $type = Type::getNamedType($field['type']);
            $fields[$relation] = Bakery::type($type->name.'Search');
        }

        return $fields;
    }
}
