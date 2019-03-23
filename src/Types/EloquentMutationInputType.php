<?php

namespace Bakery\Types;

use Bakery\Utils\Utils;
use Bakery\Fields\Field;
use Bakery\Fields\EloquentField;
use Bakery\Support\Facades\Bakery;
use Illuminate\Support\Collection;
use Bakery\Fields\PolymorphicField;
use Bakery\Types\Definitions\EloquentInputType;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

abstract class EloquentMutationInputType extends EloquentInputType
{
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
        return $this->modelSchema->getFillableFields()
            ->filter(function (Field $field) {
                return ! $field instanceof PolymorphicField && $field->setRegistry($this->registry)->getType()->isLeafType();
            });
    }

    /**
     * Get the fields for the relations of the model.
     *
     * @return Collection
     */
    protected function getRelationFields(): Collection
    {
        $relations = $this->modelSchema->getFillableRelationFields();

        return $relations->keys()->reduce(function (Collection $fields, $key) use ($relations) {
            $field = $relations[$key];

            if ($field instanceof EloquentField) {
                return $fields->merge($this->getFieldsForRelation($key, $field));
            } elseif ($field instanceof PolymorphicField) {
                return $fields->merge($this->getFieldsForPolymorphicRelation($key, $field));
            }

            return $fields;
        }, collect());
    }

    /**
     * Set the relation fields.
     *
     * @param string $relation
     * @param \Bakery\Fields\EloquentField $field
     * @return Collection
     */
    protected function getFieldsForRelation(string $relation, EloquentField $field): Collection
    {
        $fields = collect();
        $field->setRegistry($this->registry);
        $inputType = 'Create'.$field->getName().'Input';
        $relationship = $this->model->{$relation}();

        if ($field->isList()) {
            $name = str_singular($relation).'Ids';
            $fields->put($name, $this->registry->field($this->registry->ID())->list()->nullable());

            if ($this->registry->hasType($inputType)) {
                $fields->put($relation, $this->registry->field($inputType)->list()->nullable());
            }
        } else {
            $name = str_singular($relation).'Id';
            $fields->put($name, $this->registry->field($this->registry->ID())->nullable());

            if ($this->registry->hasType($inputType)) {
                $fields->put($relation, $this->registry->field($inputType)->nullable());
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
     * @param \Bakery\Fields\PolymorphicField $field
     * @return Collection
     */
    protected function getFieldsForPolymorphicRelation(string $relation, PolymorphicField $field)
    {
        $fields = collect();
        $typename = Utils::typename($relation).'On'.$this->modelSchema->typename();
        $createInputType = 'Create'.$typename.'Input';
        $attachInputType = 'Attach'.$typename.'Input';

        if ($this->registry->hasType($createInputType)) {
            if ($field->isList()) {
                $fields->put($relation, $this->registry->field($createInputType)->list()->nullable());
                $fields->put($relation.'Ids', $this->registry->field($attachInputType)->list()->nullable());
            } else {
                $fields->put($relation, $this->registry->field($createInputType)->nullable());
                $fields->put($relation.'Id', $this->registry->field($attachInputType)->nullable());
            }
        }

        return $fields;
    }

    /**
     * Get the fields for a pivot relation.
     *
     * @param \Bakery\Fields\EloquentField $field
     * @param \Illuminate\Database\Eloquent\Relations\BelongsToMany $relation
     * @return Collection
     */
    protected function getFieldsForPivot(EloquentField $field, BelongsToMany $relation): Collection
    {
        $fields = collect();
        $pivot = $relation->getPivotClass();
        $relationName = $relation->getRelationName();

        if (! $this->registry->hasSchemaForModel($pivot)) {
            return collect();
        }

        $inputType = 'Create'.Utils::typename($relationName).'On'.$this->modelSchema->typename().'WithPivotInput';
        $fields->put($relationName, $this->registry->field($inputType)->list()->nullable());

        $name = str_singular($relationName).'Ids';
        $inputType = Utils::pluralTypename($relationName).'PivotInput';
        $fields->put($name, $this->registry->field($inputType)->list()->nullable());

        return $fields;
    }
}
