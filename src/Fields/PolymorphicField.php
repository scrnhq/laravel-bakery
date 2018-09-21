<?php

namespace Bakery\Fields;

use Bakery\Utils\Utils;
use Bakery\TypeRegistry;
use Bakery\Types\Definitions\Type;

class PolymorphicField extends Field
{
    /**
     * The model schemas of a polymorphic type.
     *
     * @var array
     */
    protected $modelSchemas;

    /**
     * The type resolver.
     *
     * @var callable
     */
    protected $typeResolver;

    /**
     * PolymorphicType constructor.
     *
     * @param \Bakery\TypeRegistry $registry
     * @param array $modelSchemas
     */
    public function __construct(TypeRegistry $registry, array $modelSchemas = [])
    {
        parent::__construct($registry);

        $this->modelSchemas = $modelSchemas;
    }

    /**
     * Get the model schemas of a polymorphic type.
     *
     * @return array
     */
    public function getModelSchemas(): array
    {
        return $this->modelSchemas;
    }

    /**
     * Get the model schema by key.
     *
     * @param string $key
     * @return mixed
     */
    public function getModelSchemaByKey(string $key)
    {
        return collect($this->modelSchemas)->first(function ($definition) use ($key) {
            return Utils::single(resolve($definition)->getModel()) === $key;
        });
    }

    /**
     * Define the type resolver.
     *
     * @param callable $resolver
     * @return $this
     */
    public function typeResolver(callable $resolver)
    {
        $this->typeResolver = $resolver;

        return $this;
    }

    /**
     * Get the type resolver.
     *
     * @return callable
     */
    public function getTypeResolver()
    {
        return $this->typeResolver;
    }

    /**
     * Get the underlying (wrapped) type.
     *
     * @return \Bakery\Types\Definitions\Type
     */
    public function type(): Type
    {
        return $this->registry->type($this->name);
    }
}
