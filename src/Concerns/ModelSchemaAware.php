<?php

namespace Bakery\Concerns;

use Bakery\Eloquent\ModelSchema;
use Bakery\Exceptions\InvariantViolation;
use Bakery\Utils\Utils;

trait ModelSchemaAware
{
    /**
     * @var \Bakery\Bakery
     */
    protected $bakery;

    /**
     * A reference to the model schema.
     *
     * @var \Bakery\Eloquent\ModelSchema
     */
    protected $schema;

    /**
     * Reference to the eloquent model.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $model;

    /**
     * Construct a new model aware input type.
     *
     * @param mixed $schema
     */
    public function __construct($schema = null)
    {
        parent::__construct();

        if (isset($schema)) {
            $this->schema = $schema;
        }

        if (is_string($this->schema)) {
            $this->schema = $this->bakery->getModelSchema($this->schema);
        }

        Utils::invariant(
            $this->schema instanceof ModelSchema,
            'Invalid schema defined for '.get_class($this)
        );

        $this->model = $this->schema->getModel();
    }
}
