<?php

namespace Bakery\Types;

use Bakery\Utils\Utils;
use GraphQL\Type\Definition\Type;
use Bakery\Support\Facades\Bakery;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

class CreateInputType extends ModelAwareInputType
{
    /**
     * Get the name of the Create Input Type.
     *
     * @return string
     */
    protected function name(): string
    {
        return 'Create' . Utils::typename($this->model->getModel()) . 'Input';
    }

    /**
     * Return the fields for the Create Input Type.
     *
     * @return array
     */
    public function fields(): array
    {
        $fields = array_merge(
            $this->model->getFillableFields()->toArray(),
            $this->getRelationFields()
        );
        
        Utils::invariant(
            count($fields) > 0,
            'There are no fields defined for ' . class_basename($this->model)
        );

        return $fields;
    }

    /**
     * Get the fields for the relations of the model.
     *
     * @return array
     */
    private function getRelationFields(): array
    {
        $fields = [];

        collect($this->model->getRelations())->each(function ($type, $relation) use (&$fields) {
            $model = $this->model->getModel();

            Utils::invariant(
                method_exists($model, $relation),
                'Relation ' . $relation . ' does not exist as method on model ' . $model
            );

            $relationship = $model->{$relation}();
            $inputType = $this->inputTypeName($relationship);

            Utils::invariant(
                $relationship instanceof Relation,
                'Relation ' . $relation . ' on ' . $model . ' does not return an Eloquent relationship'
            );

            if (Utils::pluralRelationship($relationship)) {
                $name = str_singular($relation) . 'Ids';
                $fields[$name] = Bakery::listOf(Bakery::ID());

                if (Bakery::hasType($inputType)) {
                    $fields[$relation] = Bakery::listOf(Bakery::type($inputType));
                }
            }

            if (Utils::singularRelationship($relationship)) {
                $name = str_singular($relation) . 'Id';
                $fields[$name] = Bakery::ID();

                if (Bakery::hasType($inputType)) {
                    $fields[$relation] = Bakery::type($inputType);
                }
            }
        });

        return $fields;
    }

    /**
     * Generate the input type name for a relationship.
     *
     * @param Relation $relationship
     * @return string
     */
    private function inputTypeName(Relation $relationship): string
    {
        return 'Create' . class_basename($relationship->getRelated()) . 'Input';
    }
}
