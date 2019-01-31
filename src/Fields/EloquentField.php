<?php

namespace Bakery\Fields;

use Bakery\Eloquent\ModelSchema;
use Bakery\Support\TypeRegistry;
use Bakery\Types\Definitions\Type;

class EloquentField extends Field
{
    /**
     * @var string
     */
    protected $modelSchemaClass;

    /**
     * @var string
     */
    protected $inverseRelationName;

    /**
     * EloquentField constructor.
     *
     * @param \Bakery\Support\TypeRegistry $registry
     * @param string $class
     */
    public function __construct(TypeRegistry $registry, string $class)
    {
        $this->modelSchemaClass = $class;

        parent::__construct($registry);
    }

    /**
     * Set the name of the inverse relationship.
     *
     * @param string $relationName
     * @return $this
     */
    public function inverse(string $relationName): self
    {
        $this->inverseRelationName = $relationName;

        return $this;
    }

    /**
     * Get the name of the inverse relationship.
     *
     * @return string
     */
    public function getInverse(): ?string
    {
        return $this->inverseRelationName;
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
