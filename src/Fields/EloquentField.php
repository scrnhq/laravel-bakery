<?php

namespace Bakery\Fields;

use Bakery\Utils\Utils;
use Bakery\Eloquent\ModelSchema;
use Bakery\Support\TypeRegistry;
use Illuminate\Support\Collection;
use Bakery\Types\Definitions\RootType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

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
     * @var callable|null
     */
    protected $relationResolver;

    /**
     * @var callable|null
     */
    protected $collectionResolver;

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
     * @return \Bakery\Types\Definitions\RootType
     */
    protected function type(): RootType
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

    /**
     * Set a custom relation resolver.
     */
    public function relation(callable $resolver): self
    {
        $this->relationResolver = $resolver;

        return $this;
    }

    /**
     * Return the Eloquent relation.
     */
    public function getRelation(Model $model): Relation
    {
        if ($resolver = $this->relationResolver) {
            return $resolver($model);
        }

        $accessor = $this->getAccessor();

        Utils::invariant(
            method_exists($model, $accessor),
            'Relation "'.$accessor.'" is not defined on "'.get_class($model).'".'
        );

        return $model->{$accessor}();
    }

    /**
     * Determine if the field has a relation resolver.
     */
    public function hasRelationResolver(): bool
    {
        return isset($this->relationResolver);
    }

    /**
     * Get the result of the field.
     */
    public function getResult(Model $model)
    {
        if ($resolver = $this->relationResolver) {
            return $resolver($model)->get();
        }

        return $model->{$this->getAccessor()};
    }
}
