<?php

namespace Bakery\Types;

use Bakery\BakeryField;
use Bakery\Utils\Utils;
use Bakery\Concerns\ModelAware;
use GraphQL\Type\Definition\Type;
use Bakery\Support\Facades\Bakery;
use Illuminate\Support\Collection;
use GraphQL\Type\Definition\ListOfType;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
     * @return Collection
     */
    protected function getRelationFields(): Collection
    {
        $relations = $this->schema->getRelationFields();

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
    protected function getFieldsForRelation(string $relation, BakeryField $field): Collection
    {
        $fields = collect();
        $inputType = 'Create'.$field->typename().'Input';
        $relationship = $this->model->{$relation}();

        if ($field->isCollection()) {
            $name = str_singular($relation).'Ids';
            $fields->put($name, Type::listOf(Type::ID()));

            if (Bakery::hasType($inputType)) {
                $fields->put($relation, Type::listOf(Bakery::type($inputType)));
            }
        } else {
            $name = str_singular($relation).'Id';
            $fields->put($name, Type::ID());

            if (Bakery::hasType($inputType)) {
                $fields->put($relation, Bakery::type($inputType));
            }
        }

        if ($relationship instanceof BelongsToMany) {
            $fields = $fields->merge($this->getFieldsForPivot($field, $relationship));
        }

        return $fields;
    }

    /**
     * Get the fields for a pivot relation.
     *
     * @return array
     */
    protected function getFieldsForPivot(BakeryField $field, BelongsToMany $relation): Collection
    {
        $fields = collect();
        $pivot = $relation->getPivotClass();
        $relationName = $relation->getRelationName();

        if (! Bakery::hasModelSchema($pivot)) {
            return collect();
        }

        $inputType = 'Create'.Utils::pluralTypename($relationName).'WithPivotInput';
        $fields->put($relationName, Type::listOf(Bakery::type($inputType)));

        $name = str_singular($relationName).'Ids';
        $inputType = Utils::pluralTypename($relationName).'PivotInput';
        $fields->put($name, Type::listOf(Bakery::type($inputType)));

        return $fields;
    }
}
