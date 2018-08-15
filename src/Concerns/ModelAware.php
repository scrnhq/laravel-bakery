<?php

namespace Bakery\Concerns;

use Bakery\Utils\Utils;
use Bakery\Eloquent\Introspectable;

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
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $model;

    /**
     * A reference to the schema.
     *
     * @var \Bakery\Contracts\Introspectable
     */
    protected $schema;

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
            Utils::usesTrait($this->schema, Introspectable::class),
            class_basename($this->schema).' does not use the '.Introspectable::class.' trait.'
        );

        $this->model = $this->schema->getModel();
    }
}
