<?php

namespace Bakery\Fields;

use Bakery\TypeRegistry;
use Bakery\Types\Definitions\Type;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class Field
{
    /**
     * @var \Bakery\TypeRegistry
     */
    protected $registry;

    /**
     * @var \GraphQL\Type\Definition\Type
     */
    private $type;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var bool
     */
    protected $list = false;

    /**
     * @var bool
     */
    protected $nullable = false;

    /**
     * @var bool
     */
    protected $fillable = true;

    /**
     * @var bool
     */
    protected $unique = false;

    /**
     * @var mixed
     */
    protected $storePolicy;

    /**
     * @var mixed
     */
    protected $viewPolicy;

    /**
     * @var callable
     */
    protected $resolver;

    /**
     * Construct a new field.
     *
     * @param \Bakery\TypeRegistry $registry
     * @param \Bakery\Types\Definitions\Type|null $type
     */
    public function __construct(TypeRegistry $registry, Type $type = null)
    {
        $this->registry = $registry;

        if ($type) {
            $this->type = $type;
        }
    }

    /**
     * @return \Bakery\TypeRegistry
     */
    public function getRegistry(): TypeRegistry
    {
        return $this->registry;
    }

    /**
     * @param \Bakery\TypeRegistry $registry
     * @return \Bakery\Fields\Field
     */
    public function setRegistry(TypeRegistry $registry): self
    {
        $this->registry = $registry;

        return $this;
    }

    /**
     * @return \Bakery\Types\Definitions\Type
     */
    protected function type(): Type
    {
        return $this->type;
    }

    /**
     * Return the type of the field.
     *
     * @return \Bakery\Types\Definitions\Type
     */
    public function getType(): Type
    {
        $type = $this->type();

        $type->nullable($this->isNullable());
        $type->list($this->isList());

        return $type->setRegistry($this->getRegistry());
    }

    /**
     * Define the name of the field.
     *
     * This method can be overridden when extending the Field.
     *
     * @return string
     */
    protected function name(): string
    {
        return $this->name;
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
     * Set the name of the type.
     *
     * @param string $name
     * @return \Bakery\Fields\Field
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @param bool $list
     * @return \Bakery\Fields\Field
     */
    public function list(bool $list = true): self
    {
        $this->list = $list;

        return $this;
    }

    /**
     * @return bool
     */
    public function isList(): bool
    {
        return $this->list;
    }

    /**
     * Set if the field is nullable.
     *
     * @param bool $nullable
     * @return \Bakery\Fields\Field
     */
    public function nullable(bool $nullable = true): self
    {
        $this->nullable = $nullable;

        return $this;
    }

    /**
     * Return if the field is nullable.
     *
     * @return bool
     */
    public function isNullable(): bool
    {
        return $this->nullable;
    }

    /**
     * Set if the field is fillable.
     *
     * @param bool $fillable
     * @return \Bakery\Fields\Field
     */
    public function fillable(bool $fillable = true): self
    {
        $this->fillable = $fillable;

        return $this;
    }

    /**
     * Return if the field is fillable.
     *
     * @return bool
     */
    public function isFillable(): bool
    {
        return $this->fillable;
    }

    /**
     * Set if the field is unique.
     *
     * @param bool $unique
     * @return \Bakery\Fields\Field
     */
    public function unique(bool $unique = true): self
    {
        $this->unique = $unique;

        return $this;
    }

    /**
     * Return if the field is unique.
     *
     * @return bool
     */
    public function isUnique(): bool
    {
        return $this->unique;
    }

    /**
     * Set the story policy.
     *
     * @param $policy
     * @return \Bakery\Fields\Field
     */
    public function storePolicy($policy): self
    {
        $this->storePolicy = $policy;

        return $this;
    }

    /**
     * Set the store policy with a callable.
     *
     * @param callable $closure
     * @return \Bakery\Fields\Field
     */
    public function canStore(callable $closure): self
    {
        return $this->storePolicy($closure);
    }

    /**
     * Set the store policy with a reference to a policy method.
     *
     * @param string $policy
     * @return \Bakery\Fields\Field
     */
    public function canStoreWhen(string $policy): self
    {
        return $this->storePolicy($policy);
    }

    /**
     * Set the view policy.
     *
     * @param $policy
     * @return \Bakery\Fields\Field
     */
    public function viewPolicy($policy): self
    {
        $this->viewPolicy = $policy;

        return $this;
    }

    /**
     * Set the store policy with a callable.
     *
     * @param callable $closure
     * @return \Bakery\Fields\Field
     */
    public function canSee(callable $closure = null): self
    {
        return $this->viewPolicy($closure);
    }

    /**
     * Set the store policy with a reference to a policy method.
     *
     * @param string $policy
     * @return \Bakery\Fields\Field
     */
    public function canSeeWhen(string $policy): self
    {
        return $this->viewPolicy($policy);
    }

    /**
     * @return mixed
     */
    public function getViewPolicy()
    {
        return $this->viewPolicy;
    }

    /**
     * Set the resolver.
     *
     * @param $resolver
     * @return \Bakery\Fields\Field
     */
    public function resolve(callable $resolver): self
    {
        $this->resolver = $resolver;

        return $this;
    }

    /**
     * Resolve the field.
     *
     * @param $source
     * @param array $args
     * @param $context
     * @param \GraphQL\Type\Definition\ResolveInfo $info
     * @return mixed|null
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function resolveField($source, array $args, $context, ResolveInfo $info)
    {
        if (isset($this->viewPolicy)) {
            if (! $this->checkPolicy($source, $args, $context, $info)) {
                return null;
            }
        }

        if (isset($this->resolver)) {
            return call_user_func_array($this->resolver, [$source, $args, $context, $info]);
        }

        return self::defaultResolver($source, $args, $context, $info);
    }

    /**
     * Check the policy of the field to determine if the user can view the field.
     *
     * @param $source
     * @param $args
     * @param $context
     * @param ResolveInfo $info
     * @return bool
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    protected function checkPolicy($source, $args, $context, ResolveInfo $info)
    {
        $user = auth()->user();
        $policy = $this->viewPolicy;
        /** @var Gate $gate */
        $gate = app(Gate::class)->forUser($user);
        $fieldName = $info->fieldName;
        // Check if the policy method is callable
        if (($policy instanceof \Closure || is_callable_tuple($policy)) && $policy($user, $source, $args, $context, $info)) {
            return true;
        }
        // Check if there is a policy with this name
        if (is_string($policy) && $gate->check($policy, $source)) {
            return true;
        }
        if ($this->nullable) {
            return false;
        }
        throw new AuthorizationException('Cannot read property "'.$fieldName.'" of '.get_class($source));
    }

    /**
     * Check the store policy of the type.
     *
     * @param $source
     * @param $fieldName
     * @param $value
     * @return bool
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function checkStorePolicy($source, $fieldName, $value): bool
    {
        $policy = $this->storePolicy;

        // Check if there is a policy.
        if (! $policy) {
            return true;
        }

        $user = auth()->user();

        /** @var Gate $gate */
        $gate = app(Gate::class)->forUser($user);

        // Check if the policy method is a closure.
        if (($policy instanceof \Closure || is_callable_tuple($policy)) && $policy($user, $source, $value)) {
            return true;
        }

        // Check if there is a policy with this name
        if (is_string($policy) && $gate->check($policy, [$source, $value])) {
            return true;
        }

        throw new AuthorizationException('Cannot set property "'.$fieldName.'" of '.get_class($source));
    }

    /**
     * Convert the field to an array.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'type' => $this->getType()->toType(),
            'resolve' => [$this, 'resolveField'],
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
    public static function defaultResolver($source, array $args, $context, ResolveInfo $info)
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

    public function __sleep()
    {
        return ['registry'];
    }
}
