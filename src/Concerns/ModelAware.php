<?php

namespace Bakery\Concerns;

use Bakery\Eloquent\ModelSchema;
use Bakery\Support\Facades\Bakery;
use Bakery\Exceptions\InvariantViolation;

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
     * @var \Bakery\Eloquent\ModelSchema
     */
    protected $schema;

    /**
     * Construct a new model aware input type.
     *
     * @param string $class
     */
    public function __construct($class = null)
    {
        if (isset($class)) {
            $this->class = $class;
        }

        if ($class instanceof ModelSchema) {
            $this->schema = $class;
        } else if (is_string($class)) {
            $this->schema = Bakery::getModelSchema($this->class);
        } else {
            throw new InvariantViolation('Invalid schema for '.get_class($this));
        }

        $this->model = $this->schema->getModel();
    }
}
