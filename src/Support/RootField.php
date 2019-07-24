<?php

namespace Bakery\Support;

use Bakery\Exceptions\UnauthorizedException;
use Bakery\Exceptions\ValidationException;
use Bakery\Utils\Utils;
use Bakery\Types\Definitions\Type;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Support\Facades\Validator;
use GraphQL\Type\Definition\Type as GraphQLType;

abstract class RootField
{
    /**
     * @var \Bakery\Support\TypeRegistry
     */
    protected $registry;

    /**
     * Name of the field.
     *
     * @var string
     */
    protected $name;

    /**
     * The fields of the field.
     *
     * @var array
     */
    protected $fields;

    /**
     * The description of the field.
     *
     * @var string
     */
    protected $description;

    /**
     * The attributes of the RootField.
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * RootField constructor.
     *
     * @param \Bakery\Support\TypeRegistry $registry
     */
    public function __construct(TypeRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * The attributes of the RootField.
     *
     * @return array
     */
    public function attributes()
    {
        return [];
    }

    /**
     * Define the type of the RootField.
     *
     * @return Type
     */
    abstract public function type(): Type;

    /**
     * Get the underlying field of the type and convert it to a type.
     *
     * @return \GraphQL\Type\Definition\Type
     */
    public function getType(): GraphQLType
    {
        return $this->type()->toType();
    }

    /**
     * The name of the field.
     *
     * @return null|string
     */
    public function name(): ?string
    {
        return $this->name;
    }

    /**
     * Get the name of the field.
     *
     * @return string
     */
    public function getName(): string
    {
        $name = $this->name();

        Utils::invariant($name, 'RootField '.get_class($this).' has no name defined.');

        return $name;
    }

    /**
     * The arguments for the RootField.
     *
     * @return array
     */
    public function args(): array
    {
        return [];
    }

    /**
     * Get the arguments of the field and convert them to types.
     *
     * @return array
     */
    public function getArgs(): array
    {
        return collect($this->args())->map(function (Type $type) {
            return $type->toType();
        })->toArray();
    }

    /**
     * Define the fields.
     *
     * @return array
     */
    public function fields()
    {
        return $this->fields;
    }

    /**
     * Get the fields for a field.
     *
     * @return array|null
     */
    public function getFields(): ?array
    {
        return $this->fields();
    }

    /**
     * Define the description.
     *
     * @return string
     */
    public function description()
    {
        return $this->description;
    }

    /**
     * Get the description for a field.
     *
     * @return null|string
     */
    public function getDescription(): ?string
    {
        return $this->description();
    }

    /**
     * Retrieve the resolver for the RootField.
     *
     * @return callable|null
     */
    private function getResolver()
    {
        if ( ! method_exists($this, 'resolve')) {
            return null;
        }

        return [$this, 'abstractResolver'];
    }

    /**
     * @param $root
     * @param array $args
     * @param $context
     * @param ResolveInfo $info
     * @return null
     */
    public function abstractResolver($root, array $args, $context, ResolveInfo $info)
    {
        if ( ! method_exists($this, 'resolve')) {
            return null;
        }

        $args = new Arguments($args);

        if (method_exists($this, 'rules')) {
            $rules = $this->rules();
            $messages = method_exists($this, 'messages') ? $this->messages() : [];
            $attributes = method_exists($this, 'attributes') ? $this->attributes() : [];
            $validator = Validator::make($args->toArray(), $rules, $messages, $attributes);

            if ($validator->fails()) {
                throw new ValidationException($validator->getMessageBag());
            }
        }

        if (method_exists($this, 'authorize')) {
            $authorized = $this->authorize($args);
            if (!isset($authorized) && empty($authorized)) {
                throw new UnauthorizedException();
            }
        }

        return $this->resolve($args, $root, $context, $info);
    }

    /**
     * Get the attributes from the container.
     *
     * @return array
     */
    public function getAttributes()
    {
        return [
            'name' => $this->getName(),
            'args' => $this->getArgs(),
            'type' => $this->getType(),
            'fields' => $this->getFields(),
            'description' => $this->getDescription(),
            'resolve' => $this->getResolver(),
        ];
    }

    /**
     * Convert the RootField instance to an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->getAttributes();
    }

    /**
     * Get the registry.
     *
     * @return \Bakery\Support\TypeRegistry
     */
    public function getRegistry(): TypeRegistry
    {
        return $this->registry;
    }

    /**
     * Set the registry on the root field.
     *
     * @param \Bakery\Support\TypeRegistry $registry
     * @return $this
     */
    public function setRegistry(TypeRegistry $registry): self
    {
        $this->registry = $registry;

        return $this;
    }
}
