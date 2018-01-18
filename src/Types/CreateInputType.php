<?php

namespace Bakery\Types;

use GraphQL\Type\Definition\Type;
use Bakery\Support\Facades\Bakery;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations;

class CreateInputType extends InputType
{
    /**
     * The name of the type.
     *
     * @var string
     */
    protected $name;

    /**
     * A reference to the model.
     *
     * @var Model
     */
    protected $model;

    /**
     * Construct a new collection filter type.
     *
     * @param string $class
     */
    public function __construct(string $class)
    {
        $this->name = 'Create' . class_basename($class) . 'Input';
        $this->model = app($class);
    }

    /**
     * Return the fields for the collection filter type.
     *
     * @return array
     */
    public function fields(): array
    {
        return array_merge($this->getFillableFields(), $this->getRelationFields());
    }

    /**
     * Get the fillable fields of the model.
     *
     * @return array
     */
    private function getFillableFields(): array
    {
        return array_filter($this->model->fields(), function ($value, $key) {
            if (is_array($value)) {
                $type = Type::getNamedType($value['type']);
            } else {
                $type = Type::getNamedType($value);
            }
            $fillable = array_values($this->model->getFillable());

            return in_array($key, $fillable) && Type::isLeafType($type);
        }, ARRAY_FILTER_USE_BOTH);
    }

    /**
     * Get the fields for the relations of the model.
     *
     * @return array
     */
    private function getRelationFields(): array
    {
        $fields = [];

        foreach ($this->model->getFillable() as $fillable) {
            if (method_exists($this->model, $fillable)) {
                $relationship = $this->model->{$fillable}();
                $type = get_class($relationship);
                $inputType = 'Create' . class_basename($relationship->getRelated()) . 'Input';

                if ($type === Relations\HasMany::class || $type === Relations\BelongsToMany::class) {
                    $name = str_singular($fillable) . 'Ids';
                    $fields[$name] = Bakery::listOf(Bakery::ID());

                    if (Bakery::hasType($inputType)) {
                        $fields[$fillable] = Bakery::listOf(Bakery::type($inputType));
                    }
                }

                if ($type === Relations\BelongsTo::class || $type === Relations\HasOne::class) {
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
