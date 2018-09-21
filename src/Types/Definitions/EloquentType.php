<?php

namespace Bakery\Types\Definitions;

use Bakery\TypeRegistry;
use Bakery\Eloquent\ModelSchema;

class EloquentType extends ObjectType
{
    /**
     * The underlying model schema.
     *
     * @var mixed
     */
    protected $modelSchema;

    /**
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $model;

    /**
     * Construct a new Eloquent type.
     *
     * @param \Bakery\TypeRegistry $registry
     * @param \Bakery\Eloquent\ModelSchema $modelSchema
     */
    public function __construct(TypeRegistry $registry, ModelSchema $modelSchema)
    {
        parent::__construct($registry);

        $this->modelSchema = $modelSchema;
        $this->model = $this->modelSchema->getModel();
    }

    /**
     * Define the fields that should be serialized.
     *
     * @return array
     */
    public function __sleep()
    {
        $fields = [
            'modelSchema',
            'model',
        ];

        return array_merge($fields, parent::__sleep());
    }
}
