<?php

namespace Bakery;

use Bakery\Utils\Utils;
use Bakery\Fields\Field;
use Bakery\Eloquent\ModelSchema;
use Bakery\Fields\EloquentField;
use Bakery\Types\Definitions\Type;
use Bakery\Exceptions\TypeNotFound;
use Bakery\Fields\PolymorphicField;
use Illuminate\Database\Eloquent\Model;
use Bakery\Types\Definitions\InternalType;
use Bakery\Types\Definitions\ReferenceType;
use GraphQL\Type\Definition\Type as GraphQLType;

class TypeRegistry
{
    /**
     * The registered types.
     *
     * @var array
     */
    protected $types = [];

    /**
     * The GraphQL type instances.
     *
     * @var array
     */
    protected $typeInstances = [];

    /**
     * The model schema instances.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $modelSchemas;

    /**
     * The model schemas keyed by model class name.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $schemasByModel;

    /**
     * The model schemas keyed by model instance.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $schemasByInstance;

    /**
     * Bakery constructor.
     */
    public function __construct()
    {
        $this->modelSchemas = collect();
        $this->schemasByModel = collect();
        $this->schemasByInstance = collect();
    }

    /**
     * Add types to the registry.
     *
     * @param array $classes
     */
    public function addTypes(array $classes)
    {
        foreach ($classes as $class) {
            $class = is_object($class) ? $class : resolve($class);
            $this->addType($class);
        }
    }

    /**
     * Explicitly set all the types of the registry.
     *
     * @param $types
     * @return void
     */
    public function setTypes($types)
    {
        $this->types = $types;
    }

    /**
     * Get all the types of the registry.
     *
     * @return array
     */
    public function getTypes()
    {
        return $this->types;
    }

    /**
     * Add a type to the registry.
     *
     * @param \Bakery\Types\Definitions\Type $type
     * @param string|null $name
     */
    public function addType(Type $type, string $name = null)
    {
        $type->setRegistry($this);
        $name = $name ?: $type->getName();
        $this->types[$name] = $type;
    }

    /**
     * Add the models as model schemas to the registry.
     *
     * @param \ArrayAccess|array $models
     */
    public function addModelSchemas($models)
    {
        foreach ($models as $model) {
            $this->addModelSchema($model);
        }
    }

    /**
     * Add a single model schema to the registry.
     *
     * @param string $class
     */
    public function addModelSchema(string $class)
    {
        Utils::invariant(is_subclass_of($class, ModelSchema::class), 'Model schema '.$class.' does not extend '.ModelSchema::class);

        /** @var ModelSchema $schema */
        $schema = new $class($this);

        $this->modelSchemas->put($class, $schema);
        $this->schemasByModel->put($schema->getModelClass(), $class);
    }

    /**
     * Get a model schema instance based on it's class name.
     *
     * @param string $class
     * @return mixed
     */
    public function getModelSchema(string $class): ModelSchema
    {
        Utils::invariant(
            $this->modelSchemas->has($class),
            $class.' is not registered as model schema in the schema\'s type registry.'
        );

        return $this->modelSchemas->get($class);
    }

    /**
     * Return a model schema for a Eloquent model instance.
     * This 'wraps' a model schema around it.
     *
     * @param mixed $model
     * @return \Bakery\Eloquent\ModelSchema
     */
    public function getSchemaForModel(Model $model): ModelSchema
    {
        $class = get_class($model);
        $hash = spl_object_hash($model);

        Utils::invariant(
            $this->hasSchemaForModel($class),
            $class.' has no registered model schema in the schema\'s type registry.'
        );

        if ($this->schemasByInstance->has($hash)) {
            return $this->schemasByInstance->get($hash);
        }

        $class = $this->schemasByModel->get(get_class($model));

        $modelSchema = new $class($this, $model);

        $this->schemasByInstance->put($hash, $modelSchema);

        return $modelSchema;
    }

    /**
     * Resolve the schema for a model based on the class name.
     *
     * @param string $model
     * @return \Bakery\Eloquent\ModelSchema
     */
    public function resolveSchemaForModel(string $model): ModelSchema
    {
        Utils::invariant(
            $this->hasSchemaForModel($model),
            'Model '.$model.' has no registered model schema in the schema\'s type registry.'
        );

        $schema = $this->schemasByModel->get($model);

        return $this->getModelSchema($schema);
    }

    /**
     * Returns if there is a model schema for a model registered.
     *
     * @param $model
     * @return bool
     */
    public function hasSchemaForModel($model): bool
    {
        $model = is_string($model) ? $model : get_class($model);

        return $this->schemasByModel->has($model);
    }

    /**
     * Return if the name is registered as a type.
     *
     * @param string $name
     * @return bool
     */
    public function hasType(string $name): bool
    {
        return array_key_exists($name, $this->types);
    }

    /**
     * Get a type by name.
     * This can be a string or a class path of a Type that has that name.
     *
     * @param string $name
     * @return \Bakery\Types\Definitions\Type|null
     */
    public function getType(string $name): ?Type
    {
        // If the string is the name of the type we return it straight away.
        if ($this->hasType($name)) {
            return $this->types[$name];
        }

        // If the string is a class, we resolve it, check if it is an instance of type and grab it's name.
        // and then call this method again to check.
        if (class_exists($name)) {
            $instance = resolve($name);

            if ($instance instanceof Type) {
                $name = $instance->getName();

                return $this->getType($name);
            }
        }

        return null;
    }

    /**
     * Resolve a type from the registry.
     *
     * @param string $name
     * @return GraphQLType
     * @throws \Bakery\Exceptions\TypeNotFound
     */
    public function resolve(string $name): GraphQLType
    {
        $type = $this->getType($name);

        if (! $type) {
            throw new TypeNotFound('Type '.$name.' not found.');
        }

        $name = $type->getName();

        // If we already have an instance of this type, return it.
        if (isset($this->typeInstances[$name])) {
            return $this->typeInstances[$name];
        }

        // Otherwise we create it and store it for future references.
        $type = $type->toType();
        $this->typeInstances[$name] = $type;

        return $type;
    }

    /**
     * Get the type instances of Bakery.
     *
     * @return array
     */
    public function getTypeInstances()
    {
        return $this->typeInstances;
    }

    /**
     * Set the type instances.
     *
     * @param array $typeInstances
     */
    public function setTypeInstances(array $typeInstances)
    {
        $this->typeInstances = $typeInstances;
    }

    /**
     * Construct a new field.
     *
     * @param $type
     * @return \Bakery\Fields\Field
     */
    public function field($type): Field
    {
        if (is_string($type)) {
            $type = new ReferenceType($this, $type);
        }

        return new Field($this, $type);
    }

    /**
     * Create a new Eloquent field.
     *
     * @param string $modelSchema
     * @return \Bakery\Fields\EloquentField
     */
    public function eloquent(string $modelSchema): EloquentField
    {
        return new EloquentField($this, $modelSchema);
    }

    /**
     * Create a new polymorphic field.
     *
     * @param array $modelSchemas
     * @return \Bakery\Fields\PolymorphicField
     */
    public function polymorphic(array $modelSchemas): PolymorphicField
    {
        return new PolymorphicField($this, $modelSchemas);
    }

    /**
     * Construct a new type.
     *
     * @param $type
     * @return \Bakery\Types\Definitions\Type
     */
    public function type($type): Type
    {
        if (is_string($type)) {
            return (new ReferenceType($this, $type))->setRegistry($this);
        }

        return (new Type($this))->setRegistry($this);
    }

    /**
     * Construct a new ID type.
     *
     * @return \Bakery\Types\Definitions\InternalType
     */
    public function ID()
    {
        return new InternalType($this, GraphQLType::ID());
    }

    /**
     * Construct a new boolean type.
     *
     * @return \Bakery\Types\Definitions\InternalType
     */
    public function boolean()
    {
        return new InternalType($this, GraphQLType::boolean());
    }

    /**
     * Construct a new string type.
     *
     * @return \Bakery\Types\Definitions\InternalType
     */
    public function string()
    {
        return new InternalType($this, GraphQLType::string());
    }

    /**
     * Construct a new int type.
     *
     * @return \Bakery\Types\Definitions\InternalType
     */
    public function int()
    {
        return new InternalType($this, GraphQLType::int());
    }

    /**
     * Construct a new float type.
     *
     * @return \Bakery\Types\Definitions\InternalType
     */
    public function float()
    {
        return new InternalType($this, GraphQLType::float());
    }

    /**
     * Invoked when the object is being serialized.
     *
     * @return array
     */
    public function __sleep()
    {
        return ['types', 'typeInstances'];
    }

    /**
     * Invoked when the object is being unserialized.
     */
    public function __wakeup()
    {
        $this->modelSchemas = collect();
        $this->schemasByModel = collect();
        $this->schemasByInstance = collect();
    }
}
