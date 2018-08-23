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

class Type
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
     * @var callable
     */
    protected $resolver;

    /**
     * The policy for accessing the value of the type.
     *
     * @var callable|string
     */
    protected $policy;

    /**
     * The policy for storing the value of the type.
     *
     * @var callable|string
     */
    protected $storePolicy;

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
     * Define if the value of the type is unique.
     *
     * @param bool|null value
     * @return $this
     */
    public function unique(bool $value = true)
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
     * Define the resolver.
     *
     * @param callable resolver
     * @return $this
     */
    public function resolve($resolver)
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
        return function ($source, array $args, $context, ResolveInfo $info) {
            if (isset($this->policy)) {
                $this->checkPolicy($source, $args, $context, $info);
            }

            if (isset($this->resolver)) {
                return call_user_func_array($this->resolver, [$source, $args, $context, $info]);
            }

            return self::defaultResolver($source, $args, $context, $info);
        };
    }

    /**
     * Define the policy on the type.
     *
     * @param callable|string $policy
     * @return $this
     */
    public function policy($policy)
    {
        $this->policy = $policy;

        return $this;
    }

    /**
     * Check the policy of the type.
     *
     * @param $source
     * @param $args
     * @param $context
     * @param ResolveInfo $info
     * @return void
     * @throws AuthorizationException
     */
    protected function checkPolicy($source, $args, $context, ResolveInfo $info)
    {
        $user = auth()->user();
        $policy = $this->policy;
        $gate = app(Gate::class)->forUser($user);
        $fieldName = $info->fieldName;

        // Check if the policy method is callable
        if (is_callable($policy) && ! $policy($user, $source, $args, $context, $info)) {
            throw new AuthorizationException('Cannot read property "'.$fieldName.'" of '.get_class($source));
        }

        // Check if there is a policy with this name
        if (is_string($policy) && ! $gate->check($policy, $source)) {
            throw new AuthorizationException('Cannot read property "'.$fieldName.'" of '.get_class($source));
        }
    }

    /**
     * Set the story policy.
     *
     * @param $policy
     * @return \Bakery\Types\Definitions\Type
     */
    public function storePolicy($policy)
    {
        $this->storePolicy = $policy;

        return $this;
    }

    /**
     * Set the store policy with a callable.
     *
     * @param \Closure $closure
     * @return \Bakery\Types\Definitions\Type
     */
    public function canStore(\Closure $closure)
    {
        $this->storePolicy($closure);

        return $this;
    }

    /**
     * Set the store policy with a reference to a policy method.
     *
     * @param string $policy
     * @return \Bakery\Types\Definitions\Type
     */
    public function canStoreWhen(string $policy)
    {
        $this->storePolicy = $policy;

        return $this;
    }

    /**
     * Check the store policy of the type.
     *
     * @param $source
     * @param $fieldName
     * @return void
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function checkStorePolicy($source, $fieldName)
    {
        $user = auth()->user();
        $policy = $this->storePolicy;
        $gate = app(Gate::class)->forUser($user);

        // Check if the policy method is a closure.
        if ($policy instanceof \Closure && ! $policy($source)) {
            throw new AuthorizationException('Cannot set property "'.$fieldName.'" of '.get_class($source));
        }

        // Check if there is a policy with this name
        if (is_string($policy) && ! $gate->check($policy, $source)) {
            throw new AuthorizationException('Cannot set property "'.$fieldName.'" of '.get_class($source));
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
     * @return $this
     */
    public function setName($name)
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
        if ($this->isList()) {
            return false;
        }

        return GraphQLType::isLeafType($this->getNamedType());
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
    public function toType(): GraphQLType
    {
        $type = $this->getNamedType();

        return $this->isList() ? GraphQLType::listOf($type) : $type;
    }

    /**
     * Get the output type.
     * This checks if the type is nullable and if so, wrap it in a nullable type.
     *
     * @return \GraphQL\Type\Definition\OutputType
     */
    public function toOutputType(): GraphQLOutputType
    {
        $type = $this->toType();

        return $this->isNullable() ? $type : GraphQLType::nonNull($type);
    }

    /**
     * Get the input type.
     * This checks if the type is nullable and if so, wrap it in a nullable type.
     *
     * @return \GraphQL\Type\Definition\InputType
     */
    public function toInputType(): GraphQLInputType
    {
        $type = $this->toType();

        return $this->isNullable() ? $type : GraphQLType::nonNull($type);
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
            'type' => $this->toOutputType(),
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
            'type' => $this->toInputType(),
        ];
    }

    /**
     * The default resolver for resolving the value of the type.
     * This gets called when there is no custom resolver defined.
     *
     * @param $source
     * @param array $args
     * @param $context
     * @param \GraphQL\Type\Definition\ResolveInfo $info
     * @return mixed|null
     */
    protected static function defaultResolver($source, array $args, $context, ResolveInfo $info)
    {
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

        return $property instanceof \Closure ? $property($source, $args, $context, $info) : $property;
    }
}
