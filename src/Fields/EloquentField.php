<?php

namespace Bakery\Fields;

use Bakery\Eloquent\ModelSchema;
use Bakery\TypeRegistry;
use Bakery\Types\Definitions\Type;

class EloquentField extends Field
{
    /**
     * @var string
     */
    protected $modelSchemaClass;

    /**
     * EloquentField constructor.
     *
     * @param \Bakery\TypeRegistry $registry
     * @param string $class
     */
    public function __construct(TypeRegistry $registry, string $class)
    {
        $this->modelSchemaClass = $class;

        parent::__construct($registry);
    }

    /**
     * Get the type of the Eloquent field.
     *
     * @return \Bakery\Types\Definitions\Type
     */
    protected function type(): Type
    {
        return $this->registry->type($this->getName());
    }

    /**
     * @return \Bakery\Eloquent\ModelSchema
     */
    protected function getModelClass(): ModelSchema
    {
        return $this->registry->getModelSchema($this->modelSchemaClass);
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return $this->getModelClass()->getTypename();
    }
}
