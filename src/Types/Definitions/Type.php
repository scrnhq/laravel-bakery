<?php

namespace Bakery\Types\Definitions;

use Bakery\Utils\Utils;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Contracts\Auth\Access\Gate;
use GraphQL\Type\Definition\Type as GraphQLType;
use Illuminate\Auth\Access\AuthorizationException;
use GraphQL\Type\Definition\InputType as GraphQLInputType;
use GraphQL\Type\Definition\NamedType as GraphQLNamedType;
use GraphQL\Type\Definition\OutputType as GraphQLOutputType;

abstract class Type
{
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
     * Define if the type is unique and can be used to lookup a model.
     *
     * @var bool
     */
    protected $unique = false;

    /**
     * The resolver for resolving the value of the type.
     *
     * @var mixed
     */
    protected $resolver;

    /**
     * The policy for accessing the value of the type.
     *
     * @var mixed
     */
    protected $policy;

    /**
     * Construct a new type.
     *
     * @param null $type
     */
    public function __construct($type = null)
    {
        if ($type) {
            $this->type = $type;
        }
    }

    /**
     * Set a description.
     *
     * @param string $value
     * @return self
     */
    public function description(string $value): self
    {
        $this->description = $value;

        return $this;
    }

    /**
     * Define if the type is nullable.
     *
     * @param bool value
     * @return self
     */
    public function nullable(bool $value = true): self
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
     * Define if the value of the type is unique.
     *
     * @param bool value
     * @return self
     */
    public function unique(bool $value = true): self
    {
        $this->unique = $value;

        return $this;
    }

    /**
     * Return if the value of the type is unique.
     *
     * @return bool
     */
    public function isUnique(): bool
    {
        return $this->unique;
    }

    /**
     * Define if the type is a list.
     *
     * @param bool value
     * @return self
     */
    public function list(bool $value = true): self
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
     * Define the resolver.
     *
     * @param mixed resolver
     * @return self
     */
    public function resolve($resolver): self
    {
        $this->resolver = $resolver;

        return $this;
    }

    /**
     * Get the resolver for the type.
     *
     * @return callable
     */
    protected function getResolver()
    {
        return function ($source, $args, $viewer, ResolveInfo $info) {
            if (isset($this->policy)) {
                $this->checkPolicy($source, $args, $viewer, $info);
            }

            if (isset($this->resolver)) {
                return call_user_func_array($this->resolver, [$source, $args, $viewer]);
            }

            $fieldName = $info->fieldName;
            $property = null;

            if (is_array($source) || $source instanceof \ArrayAccess) {
                if (isset($source[$fieldName])) {
                    $property = $source[$fieldName];
                }
            } elseif (is_object($source)) {
                if (isset($source->{$fieldName})) {
                    $property = $source->{$fieldName};
                }
            }

            return $property instanceof \Closure ? $property($source, $args, $viewer, $info) : $property;
        };
    }

    /**
     * Define the policy on the type.
     *
     * @param $policy
     * @return self
     */
    public function policy($policy): self
    {
        $this->policy = $policy;

        return $this;
    }

    /**
     * Check the policy of the type.
     *
     * @param $source
     * @param $args
     * @param $viewer
     * @param ResolveInfo $info
     * @return void
     * @throws AuthorizationException
     */
    protected function checkPolicy($source, $args, $viewer, ResolveInfo $info)
    {
        $policy = $this->policy;
        $gate = app(Gate::class)->forUser($viewer);
        $fieldName = $info->fieldName;

        // Check if the policy method is callable
        if (is_callable($policy) && ! $policy($source, $args, $viewer, $info)) {
            throw new AuthorizationException(
                'Cannot read property "'.$fieldName.'" of '.get_class($source)
            );
        }

        // Check if there is a policy with this name
        if (is_string($policy) && ! $gate->check($policy, $source)) {
            throw new AuthorizationException('Cannot read property "'.$fieldName.'" of '.get_class($source));
        }
    }

    /**
     * If no name is specified fall back on an
     * automatically generated name based on the class name.
     *
     * @return string
     */
    public function name(): string
    {
        if (isset($this->name)) {
            return $this->name;
        }

        return studly_case(str_before(class_basename($this), 'Type'));
    }

    /**
     * Set a name on the type.
     *
     * @param $name
     * @return Type
     */
    public function  setName($name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Returns if the underlying type is a leaf type.
     *
     * @return bool
     */
    public function isLeafType(): bool
    {
        return GraphQLType::isLeafType($this->type);
    }

    /**
     * Get the underlying (wrapped) type.
     *
     * @return GraphQLNamedType
     */
    public function getNamedType(): GraphQLNamedType
    {
        $type = method_exists($this, 'type') ? $this->type() : $this->type;
        Utils::invariant($type, 'No type defined on '.get_class($this));

        return $type;
    }

    /**
     * Get the type.
     *
     * @return GraphQLType
     */
    public function getType(): GraphQLType
    {
        $type = $this->getNamedType();

        return $this->list ? GraphQLType::listOf($type) : $type;
    }

    /**
     * Convert the type to a GraphQL Type.
     *
     * @return GraphQLType
     */
    public function toType(): GraphQLType
    {
        return $this->getType();
    }

    /**
     * Get the output type.
     * This checks if the type is nullable and if so, wrap it in a nullable type.
     *
     * @return GraphQLOutputType
     */
    public function getOutputType(): GraphQLOutputType
    {
        $type = $this->toType();

        return $this->nullable ? $type : GraphQLType::nonNull($type);
    }

    /**
     * Get the input type.
     * This checks if the type is nullable and if so, wrap it in a nullable type.
     *
     * @return GraphQLInputType
     */
    public function getInputType(): GraphQLInputType
    {
        $type = $this->toType();

        return $this->nullable ? $type : GraphQLType::nonNull($type);
    }

    /**
     * Convert the type to a field.
     *
     * @return array
     */
    public function toField(): array
    {
        return [
            'policy' => $this->policy,
            'type' => $this->getOutputType(),
            'resolve' => $this->getResolver(),
        ];
    }

    /**
     * Convert the type to an input field.
     *
     * @return array
     */
    public function toInputField(): array
    {
        return [
            'type' => $this->getInputType(),
        ];
    }
}
