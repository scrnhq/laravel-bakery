<?php

namespace Bakery\Types\Definitions;

use Bakery\TypeRegistry;
use Bakery\Utils\Utils;
use Illuminate\Contracts\Auth\Access\Gate;
use GraphQL\Type\Definition\Type as GraphQLType;
use GraphQL\Type\Definition\NamedType as GraphQLNamedType;

class Type
{
    /**
     * @var \Bakery\TypeRegistry
     */
    protected $registry;

    /**
     * @var Gate
     */
    protected $gate;

    /**
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $model;

    /**
     * The name of the type.
     *
     * @var string
     */
    protected $name;

    /**
     * The description of the type.
     *
     * @var string
     */
    protected $description;

    /**
     * The underlying type.
     *
     * @var GraphQLNamedType
     */
    protected $type;

    /**
     * Whether the type is nullable.
     *
     * @var bool
     */
    protected $nullable = false;

    /**
     * Whether the type is a list.
     *
     * @var bool
     */
    protected $list = false;

    /**
     * Construct a new type.
     *
     * @param \Bakery\TypeRegistry $registry
     * @param \GraphQL\Type\Definition\Type $type
     */
    public function __construct(TypeRegistry $registry, GraphQLType $type = null)
    {
        $this->registry = $registry;

        if ($type) {
            $this->type = $type;
        }
    }

    /**
     * Get the type registry.
     *
     * @return \Bakery\TypeRegistry
     */
    public function getRegistry(): TypeRegistry
    {
        return $this->registry;
    }

    /**
     * Set the type registry.
     *
     * @param \Bakery\TypeRegistry $registry
     * @return \Bakery\Types\Definitions\Type
     */
    public function setRegistry(TypeRegistry $registry): self
    {
        $this->registry = $registry;

        return $this;
    }

    /**
     * Define the underlying type.
     *
     * This can be overridden when extending the type.
     *
     * @return \GraphQL\Type\Definition\NamedType
     */
    protected function type(): GraphQLNamedType
    {
        return $this->type;
    }

    /**
     * Get the underlying type.
     *
     * @return \GraphQL\Type\Definition\NamedType
     */
    public function getType(): GraphQLNamedType
    {
        return $this->type();
    }

    /**
     * Set a description.
     *
     * @param string $value
     * @return $this
     */
    public function description(string $value)
    {
        $this->description = $value;

        return $this;
    }

    /**
     * Define if the type is nullable.
     *
     * @param bool value
     * @return $this
     */
    public function nullable(bool $value = true)
    {
        $this->nullable = $value;

        return $this;
    }

    /**
     * Return if the type is nullable.
     *
     * @return bool
     */
    public function isNullable(): bool
    {
        return $this->nullable;
    }

    /**
     * Define if the type is a list.
     *
     * @param bool|null $value
     * @return $this
     */
    public function list(bool $value = true)
    {
        $this->list = $value;

        return $this;
    }

    /**
     * Returns if the type is a list.
     *
     * @return bool
     */
    public function isList(): bool
    {
        return $this->list;
    }

    /**
     * Set a name on the type.
     *
     * @param $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Define the name of the type.
     *
     * This method can be overridden when extending the type.
     *
     * @return string
     */
    protected function name(): string
    {
        if (isset($this->name)) {
            return $this->name;
        }

        return Utils::typename(str_before(class_basename($this), 'Type'));
    }

    /**
     * Get the name of the type.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name();
    }

    /**
     * Returns if the underlying type is a leaf type.
     *
     * @return bool
     */
    public function isLeafType(): bool
    {
        if ($this->isList()) {
            return false;
        }

        return GraphQLType::isLeafType($this->getType());
    }

    /**
     * Convert the Bakery type to a GraphQL type.
     *
     * @return GraphQLType
     */
    public function toType(): GraphQLType
    {
        $type = $this->getType();
        $type = $this->isList() ? GraphQLType::listOf($type) : $type;
        $type = $this->isNullable() ? $type : GraphQLType::nonNull($type);

        return $type;
    }

    /**
     * Convert the Bakery type to a GraphQL (named) type.
     *
     * @return \GraphQL\Type\Definition\NamedType
     */
    public function toNamedType(): GraphQLNamedType
    {
        return $this->getType();
    }

    /**
     * Invoked when the object is being serialized.
     *
     * @return array
     */
    public function __sleep()
    {
        return [
            'type',
            'list',
            'nullable',
            'registry',
        ];
    }

    /**
     * Invoked when the object is unserialized.
     */
    public function __wakeup()
    {
        //
    }
}
