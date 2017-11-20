<?php

namespace Bakery\Types;

use ReflectionMethod;
use ReflectionException;
use Illuminate\Database\Eloquent\Model;
use Bakery\Support\Facades\Bakery;
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
        $fields = $this->model->fields();

        foreach ($this->model->getFillable() as $fillable) {
            if (method_exists($this->model, $fillable)) {
                $relationship = $this->model->{$fillable}();
                $type = get_class($relationship);
                if ($type === Relations\HasMany::class || $type === Relations\BelongsToMany::class) {
                    $name = str_singular($fillable) . 'Ids';
                    $fields[$name] = Bakery::listOf(Bakery::ID());

                    $inputType = 'Create' . title_case(str_singular($fillable)) . 'Input';
                    if (Bakery::hasType($inputType)) {
                        $fields[$fillable] = Bakery::listOf(Bakery::type($inputType));
                    }
                }

                if ($type === Relations\BelongsTo::class || $type === Relations\HasOne::class) {
                    $name = str_singular($fillable) . 'Id';
                    $fields[$name] = Bakery::ID();

                    $inputType = 'Create' . title_case(str_singular($fillable)) . 'Input';
                    if (Bakery::hasType($inputType)) {
                        $fields[$fillable] = Bakery::type($inputType);
                    }
                }
            }
        };

        return $fields;
    }
}
