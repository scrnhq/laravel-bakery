<?php

namespace Bakery\Concerns;

use Bakery\Utils\Utils;
use Bakery\Eloquent\ModelSchema;
use Bakery\Eloquent\BakeryMutable;
use Illuminate\Database\Eloquent\Model;

trait ModelAware
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
    public function __construct(string $class = null)
    {
        if (isset($class)) {
            $this->class = $class;
        }

        Utils::invariant(
            $this->class,
            'No class defined.'
        );

        $this->schema = resolve($this->class);

        Utils::invariant(
            Utils::usesTrait($this->schema, ModelSchema::class),
            class_basename($this->schema).' does not use the '.ModelSchema::class.' trait.'
        );

        $this->model = $this->schema->getModel();

        Utils::invariant(
            Utils::usesTrait($this->model, BakeryMutable::class),
            class_basename($this->model).' does not use the '.BakeryMutable::class.' trait.'
        );
    }
}
