<?php

namespace Bakery\Types;

use Bakery\Utils\Utils;
use Bakery\Support\Facades\Bakery;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\Type;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations;

class UpdateInputType extends ModelAwareInputType
{
    /**
     * Get the name of the Update Input Type.
     *
     * @return string
     */
    protected function name(): string
    {
        return 'Update' . Utils::typename($this->model->getModel()) . 'Input';
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
            'There are no fields defined for ' . class_basename($this->model)
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
    private function getRelationFields(): array
    {
        $fields = [];

        $model = $this->model->getModel();
        foreach ($model->getFillable() as $fillable) {
            if (method_exists($model, $fillable)) {
                $relationship = $model->{$fillable}();
                $inputType = 'Create' . class_basename($relationship->getRelated()) . 'Input';

                if ($relationship instanceof Relations\HasMany || $relationship instanceof Relations\BelongsToMany) {
                    $name = str_singular($fillable) . 'Ids';
                    $fields[$name] = Bakery::listOf(Bakery::ID());

                    if (Bakery::hasType($inputType)) {
                        $fields[$fillable] = Bakery::listOf(Bakery::type($inputType));
                    }
                }

                if ($relationship instanceof Relations\BelongsTo || $relationship instanceof Relations\HasOne) {
                    $name = str_singular($fillable) . 'Id';
                    $fields[$name] = Bakery::ID();

                    if (Bakery::hasType($inputType)) {
                        $fields[$fillable] = Bakery::type($inputType);
                    }
                }
            }
        };

        return $fields;
    }
}
