<?php

namespace Bakery\Types;

use Bakery\Utils\Utils;
use Bakery\Support\Facades\Bakery;
use Bakery\Types\Definitions\Type;
use Illuminate\Support\Collection;
use Bakery\Concerns\ModelSchemaAware;
use Bakery\Types\Definitions\InputType;
use Bakery\Types\Definitions\EloquentType;
use Bakery\Types\Definitions\PolymorphicType;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

abstract class MutationInputType extends InputType
{
    use ModelSchemaAware;

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
        $relations = $this->schema->getFillableRelationFields();

        return $relations->keys()->reduce(function (Collection $fields, $key) use ($relations) {
            $field = $relations[$key];

            if ($field instanceof EloquentType) {
                return $fields->merge($this->getFieldsForRelation($key, $field));
            } elseif ($field instanceof PolymorphicType) {
                return $fields->merge($this->getFieldsForPolymorphicRelation($key, $field));
            }

            return $fields;
        }, collect());
    }

    /**
     * Set the relation fields.
     *
     * @param string $relation
     * @param \Bakery\Types\Definitions\EloquentType|\Bakery\Types\Definitions\PolymorphicType $field
     * @return Collection
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
                $fields->put($relation, Bakery::type($inputType)->list()->nullable());
            }
        } else {
            $name = str_singular($relation).'Id';
            $fields->put($name, Bakery::ID()->nullable());

            if (Bakery::hasType($inputType)) {
                $fields->put($relation, Bakery::type($inputType)->nullable());
            }
        }

        if ($relationship instanceof BelongsToMany) {
            $fields = $fields->merge($this->getFieldsForPivot($field, $relationship));
        }

        return $fields;
    }

    /**
     * Get the polymorphic relation fields.
     *
     * @param string $relation
     * @param \Bakery\Types\Definitions\PolymorphicType $field
     * @return Collection
     */
    protected function getFieldsForPolymorphicRelation(string $relation, PolymorphicType $field)
    {
        $fields = collect();
        $typename = Utils::typename($relation).'On'.$this->schema->typename();
        $createInputType = 'Create'.$typename.'Input';
        $attachInputType = 'Attach'.$typename.'Input';

        if (Bakery::hasType($createInputType)) {
            if ($field->isList()) {
                $fields->put($relation, Bakery::type($createInputType)->list()->nullable());
                $fields->put($relation.'Ids', Bakery::type($attachInputType)->list()->nullable());
            } else {
                $fields->put($relation, Bakery::type($createInputType)->nullable());
                $fields->put($relation.'Id', Bakery::type($attachInputType)->nullable());
            }
        }

        return $fields;
    }

    /**
     * Get the fields for a pivot relation.
     *
     * @param \Bakery\Types\Definitions\EloquentType|\Bakery\Types\Definitions\PolymorphicType $field
     * @param \Illuminate\Database\Eloquent\Relations\BelongsToMany $relation
     * @return Collection
     */
    protected function getFieldsForPivot(EloquentType $field, BelongsToMany $relation): Collection
    {
        $fields = collect();
        $pivot = $relation->getPivotClass();
        $relationName = $relation->getRelationName();

        if (! Bakery::hasSchemaForModel($pivot)) {
            return collect();
        }

        $inputType = 'Create'.Utils::typename($relationName).'On'.$this->schema->typename().'WithPivotInput';
        $fields->put($relationName, Bakery::type($inputType)->list()->nullable());

        $name = str_singular($relationName).'Ids';
        $inputType = Utils::pluralTypename($relationName).'PivotInput';
        $fields->put($name, Bakery::type($inputType)->list()->nullable());

        return $fields;
    }
}
