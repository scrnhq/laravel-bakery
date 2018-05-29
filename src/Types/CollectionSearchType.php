<?php

namespace Bakery\Types;

use Bakery\Utils\Utils;
use GraphQL\Type\Definition\Type;
use Bakery\Support\Facades\Bakery;
use GraphQL\Type\Definition\IDType;
use GraphQL\Type\Definition\StringType;
use Illuminate\Database\Eloquent\Model;

class CollectionSearchType extends ModelAwareInputType
{
    /**
     * Get the name of the Collection Search Type.
     *
     * @return string
     */
    protected function name(): string
    {
        return Utils::typename($this->model->getModel()) . 'Search';
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
            if (is_array($type)) {
                $type = Type::getNamedType($type['type']);
            } else {
                $type = Type::getNamedType($type);
            }
            if ($type instanceof StringType || $type instanceof IDType) {
                $fields[$name] = Bakery::boolean();
            }
        }

        foreach ($this->model->relations() as $relation => $type) {
            if (is_array($type)) {
                $type = Type::getNamedType($type['type']);
            } else {
                $type = Type::getNamedType($type);
            }
            $fields[$relation] = Bakery::type($type->name . 'Search');
        }

        return $fields;
    }
}
