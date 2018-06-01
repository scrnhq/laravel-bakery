<?php

namespace Bakery\Types;

use Bakery\Utils\Utils;
use Bakery\Concerns\ModelAware;
use Bakery\Support\Facades\Bakery;
use GraphQL\Type\Definition\ListOfType;

abstract class MutationInputType extends InputType
{
    use ModelAware;

    /**
     * Get the fields for the relations of the model.
     *
     * @return array
     */
    protected function getRelationFields(): array
    {
        $relations = $this->schema->getRelations();

        return $relations->keys()->reduce(function ($fields, $key) use ($relations) {
            $field = $relations[$key];

            return $fields->merge($this->getFieldsForRelation($key, $field));
        }, collect())->toArray();
    }

    /**
     * Set the relation fields.
     *
     * @param string $relation
     * @param array $field
     * @return array
     */
    protected function getFieldsForRelation(string $relation, array $field): array
    {
        $fields = [];
        $field = Utils::nullifyField($field);
        $inputType = $this->inputTypename($relation);

        if ($field['type'] instanceof ListOfType) {
            $name = str_singular($relation).'Ids';
            $fields[$name] = Bakery::listOf(Bakery::ID());

            if (Bakery::hasType($inputType)) {
                $fields[$relation] = Bakery::listOf(Bakery::type($inputType));
            }
        } else {
            $name = str_singular($relation).'Id';
            $fields[$name] = Bakery::ID();

            if (Bakery::hasType($inputType)) {
                $fields[$relation] = Bakery::type($inputType);
            }
        }

        return $fields;
    }

    abstract protected function inputTypeName(string $relation): string;
}
