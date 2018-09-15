<?php

namespace Bakery;

use Bakery\Eloquent\ModelSchema;
use GraphQL\GraphQL;
use Bakery\Utils\Utils;
use GraphQL\Type\Schema;
use Bakery\Traits\BakeryTypes;
use Bakery\Types\Definitions\Type;
use Bakery\Exceptions\TypeNotFound;
use GraphQL\Executor\ExecutionResult;
use Bakery\Types\Definitions\NamedType;
use Illuminate\Database\Eloquent\Model;
use Bakery\Support\Schema as BakerySchema;
use GraphQL\Type\Definition\NamedType as GraphQLNamedType;

class Bakery
{
    use BakeryTypes;

    /**
     * The schemas.
     *
     * @var array
     */
    protected $schemas = [];

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
     * @var \Illuminate\Support\Collection
     */
    protected  $schemasByInstance;

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
     * Add a type to the registry.
     *
     * @param \Bakery\Types\Definitions\NamedType $type
     * @param string|null $name
     */
    public function addType(NamedType $type, string $name = null)
    {
        $name = $name ?: $type->name();
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
        $schema = resolve($class);
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
            $class.' is not registered as model schema in Bakery.'
        );

        return $this->modelSchemas->get($class);
    }

    /**
     * Return a model schema based on the model provided.
     * This can either be an instance or a class name.
     *
     * @param mixed $model
     * @return \Bakery\Eloquent\ModelSchema
     */
    public function getSchemaForModel($model): ModelSchema
    {
        $class = is_string($model) ? $model : get_class($model);

        Utils::invariant(
            $this->hasSchemaForModel($class),
            $class.' has no registered model schema in Bakery.'
        );

        $modelSchema = $this->schemasByModel->get($class);

        // If we get a string passed in we just grab the 'general' model schema.
        if (is_string($model)) {
            return $this->getModelSchema($modelSchema);
        }

        // Otherwise we create a model schema for a specific model instance.
        $hash = spl_object_hash($model);

        if ($this->schemasByInstance->has($hash)) {
            return $this->schemasByInstance->get($hash);
        }

        $class = $this->schemasByModel->get(get_class($model));

        $modelSchema = new $class($model);

        $this->schemasByInstance->put($hash, $modelSchema);

        return $modelSchema;
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
     * @return Type|null
     */
    public function getType(string $name): ?NamedType
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
                $name = $instance->name();

                return $this->getType($name);
            }
        }

        return null;
    }

    /**
     * Get the default GraphQL schema.
     *
     * @return \GraphQL\Type\Schema
     */
    public function schema(): Schema
    {
        $schema = new Support\DefaultSchema();

        return $schema->toGraphQLSchema();
    }

    /**
     * Resolve a type from the registry.
     *
     * @param string $name
     * @return \GraphQL\Type\Definition\NamedType
     * @throws \Bakery\Exceptions\TypeNotFound
     */
    public function resolve(string $name): GraphQLNamedType
    {
        $type = $this->getType($name);

        if (! $type) {
            throw new TypeNotFound('Type '.$name.' not found.');
        }

        $name = $type->name();

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
     * Execute the GraphQL query.
     *
     * @param array $input
     * @param \GraphQL\Type\Schema|\Bakery\Support\Schema $schema
     * @return \GraphQL\Executor\ExecutionResult
     */
    public function executeQuery($input, $schema = null): ExecutionResult
    {
        if (! $schema) {
            $schema = $this->schema();
        } elseif ($schema instanceof BakerySchema) {
            $schema = $schema->toGraphQLSchema();
        }

        $root = null;
        $context = auth()->user();
        $query = array_get($input, 'query');
        $variables = array_get($input, 'variables');
        if (is_string($variables)) {
            $variables = json_decode($variables, true);
        }
        $operationName = array_get($input, 'operationName');

        return GraphQL::executeQuery($schema, $query, $root, $context, $variables, $operationName);
    }

    /**
     * Serve the GraphiQL tool.
     *
     * @param $route
     * @param array $headers
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function graphiql($route, $headers = [])
    {
        return view(
            'bakery::graphiql',
            ['endpoint' => route($route), 'headers' => $headers]
        );
    }
}
