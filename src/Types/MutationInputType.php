<?php

namespace Bakery\Types;

use Bakery\Utils\Utils;
use Bakery\Concerns\ModelAware;
use GraphQL\Type\Definition\Type;
use Bakery\Support\Facades\Bakery;
use Illuminate\Support\Collection;
use GraphQL\Type\Definition\ListOfType;

abstract class MutationInputType extends InputType
{
    use ModelAware;

    /**
     * Get the fillable fields for the input type.
     *
     * Here we grab the fillable fields from the model and filter out the
     * ones that are not a leaf type. Right now Bakery only supports passing
     * leaf types or list of leaf types as input. Complex, nested input only
     * work with relations.
     *
     * @return Collection
     */
    protected function getFillableFields(): Collection
    {
        return $this->schema->getFillableFields()->filter(function ($field, $key) {
            $fieldType = Type::getNamedType($field['type']);

            return Type::isLeafType($fieldType);
        });
    }

    /**
     * Get the fields for the relations of the model.
     *
     * @return array
     */
    protected function getRelationFields(): Collection
    {
        $relations = $this->schema->getRelations();

        return $relations->keys()->reduce(function ($fields, $key) use ($relations) {
            $field = $relations[$key];

            return $fields->merge($this->getFieldsForRelation($key, $field));
        }, collect());
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
        $fieldType = Type::getNamedType($field['type']);
        $inputType = 'Create'.Utils::typename($fieldType->name).'Input';

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
}
