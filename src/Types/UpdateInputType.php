<?php

namespace Bakery\Types;

use Bakery\Utils\Utils;
use GraphQL\Type\Definition\Type;
use Bakery\Support\Facades\Bakery;
use GraphQL\Type\Definition\NonNull;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations;
use Illuminate\Database\Eloquent\Relations\Relation;

class UpdateInputType extends ModelAwareInputType
{
    /**
     * Get the name of the Update Input Type.
     *
     * @return string
     */
    protected function name(): string
    {
        return 'Update'.Utils::typename($this->model->getModel()).'Input';
    }

    /**
     * Return the fields for theUpdate Input Type.
     *
     * @return array
     */
    public function fields(): array
    {
        $fields = array_merge(
            $this->getFillableFields(),
            $this->getRelationFields()
        );

        Utils::invariant(
            count($fields) > 0,
            'There are no fields defined for '.class_basename($this->model)
        );

        return $fields;
    }

    /**
     * Get the fillable fields of the model.
     *
     * Updating in Bakery works like PATCH you only have to pass in
     * the values you want to update. The rest stays untouched.
     * Because of that we have to remove the nonNull wrappers on the fields.
     *
     * @return array
     */
    private function getFillableFields(): array
    {
        return Utils::nullifyFields($this->model->getFillableFields())->toArray();
    }

    /**
     * Get the fields for the relations of the model.
     *
     * @return array
     */
    protected function getRelationFields(): array
    {
        return collect($this->model->getRelations())->keys()->reduce(function ($fields, $relation) {
            $model = $this->model->getModel();

            Utils::invariant(
                method_exists($model, $relation),
                'Relation '.$relation.' does not exist as method on model '.class_basename($model)
            );

            $relationship = $model->{$relation}();

            Utils::invariant(
                $relationship instanceof Relation,
                'Relation '.$relation.' on '.class_basename($model).' does not return an instance of '.Relation::class
            );

            return $fields->merge($this->getFieldsForRelation($relation, $relationship));
        }, collect())->toArray();
    }

    /**
     * Set the relation fields.
     *
     * @param string $relation
     * @param Relation $relationship
     * @param array $fields
     * @return void
     */
    protected function getFieldsForRelation(string $relation, Relation $relationship): array
    {
        $fields = [];
        $inputType = $this->inputTypeName($relationship);

        if (Utils::pluralRelationship($relationship)) {
            $name = str_singular($relation).'Ids';
            $fields[$name] = Bakery::listOf(Bakery::ID());

            if (Bakery::hasType($inputType)) {
                $fields[$relation] = Bakery::listOf(Bakery::type($inputType));
            }
        }

        if (Utils::singularRelationship($relationship)) {
            $name = str_singular($relation).'Id';
            $fields[$name] = Bakery::ID();

            if (Bakery::hasType($inputType)) {
                $fields[$relation] = Bakery::type($inputType);
            }
        }

        return $fields;
    }

    /**
     * Generate the input type name for a relationship.
     *
     * @param Relation $relationship
     * @return string
     */
    protected function inputTypeName(Relation $relationship): string
    {
        return 'Update'.class_basename($relationship->getRelated()).'Input';
    }
}
