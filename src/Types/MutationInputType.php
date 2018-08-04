<?php

namespace Bakery\Types;

use Bakery\Utils\Utils;
use Bakery\Concerns\ModelAware;
use Bakery\Support\Facades\Bakery;
use Bakery\Types\Definitions\Type;
use Illuminate\Support\Collection;
use Bakery\Types\Definitions\EloquentType;
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
        return $this->schema->getFillableFields()
            ->filter(function (Type $field, $key) {
                return $field->isLeafType();
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
    protected function getFieldsForRelation(string $relation, EloquentType $field): Collection
    {
        $fields = collect();
        $inputType = 'Create'.$field->name().'Input';
        $relationship = $this->model->{$relation}();

        if ($field->isList()) {
            $name = str_singular($relation).'Ids';
            $fields->put($name, Bakery::ID()->list()->nullable());

            if (Bakery::hasType($inputType)) {
                $fields->put($relation, Bakery::resolve($inputType)->list()->nullable());
            }
        } else {
            $name = str_singular($relation).'Id';
            $fields->put($name, Bakery::ID()->nullable());

            if (Bakery::hasType($inputType)) {
                $fields->put($relation, Bakery::resolve($inputType)->nullable());
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
    protected function getFieldsForPivot(EloquentType $field, BelongsToMany $relation): Collection
    {
        $fields = collect();
        $pivot = $relation->getPivotClass();
        $relationName = $relation->getRelationName();

        if (! Bakery::hasModelSchema($pivot)) {
            return collect();
        }

        $inputType = 'Create'.Utils::pluralTypename($relationName).'WithPivotInput';
        $fields->put($relationName, Bakery::resolve($inputType)->list()->nullable());

        $name = str_singular($relationName).'Ids';
        $inputType = Utils::pluralTypename($relationName).'PivotInput';
        $fields->put($name, Bakery::resolve($inputType)->list()->nullable());

        return $fields;
    }
}
