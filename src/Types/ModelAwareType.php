<?php

namespace Bakery\Types;

use Bakery\Utils\Utils;
use Bakery\Eloquent\BakeryModel;

class ModelAwareType extends Type
{
    /**
     * A reference to the class.
     *
     * @var string
     */
    protected $class;

    /**
     * A reference to the model.
     *
     * @var Model
     */
    protected $model;

    /**
     * Construct a new model aware input type.
     *
     * @param string $class
     */
    public function __construct(string $class)
    {
        if (isset($class)) {
            $this->class = $class;
        }

        Utils::invariant(
            $this->class,
            'No class defined.'
        );

        $this->model = resolve($this->class);

        Utils::invariant(
            $this->model instanceof BakeryModel,
            class_basename($this->model).' is not an instance of '.BakeryModel::class
        );
    }
}
