<?php

namespace Bakery;

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
use GraphQL\Type\Definition\Type as GraphQLType;
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
     * The registered model schemas.
     *
     * @var array
     */
    protected $modelSchemas = [];

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
     * @param mixed $model
     */
    public function addModelSchema($model)
    {
        if (! is_subclass_of($model, Model::class)) {
            $this->modelSchemas[$model::$model] = $model;
        } else {
            $this->modelSchemas[$model] = $model;
        }
    }

    /**
     * Return a model schema based on the model provided.
     * This can either be an instance or a class name.
     *
     * @param mixed $model
     * @return mixed
     */
    public function getModelSchema($model)
    {
        $model = is_string($model) ? $model : get_class($model);

        Utils::invariant(
            array_key_exists($model, $this->modelSchemas),
            $model.' has no registered model schema in Bakery.'
        );

        return $this->modelSchemas[$model];
    }

    /**
     * Return the definition of a model.
     *
     * @param $model
     * @return mixed
     */
    public function definition($model)
    {
        return resolve($this->getModelSchema($model));
    }

    /**
     * Resolve the type of a definition of a model.
     *
     * @param $model
     * @return \GraphQL\Type\Definition\NamedType
     * @throws \Bakery\Exceptions\TypeNotFound
     */
    public function resolveDefinitionType($model): GraphQLNamedType
    {
        return $this->resolve($this->definition($model)->typename());
    }

    /**
     * Return if the model has a model schema in Bakery.
     * This can either be an instance or a class name.
     *
     * @param mixed $model
     * @return bool
     */
    public function hasModelSchema($model)
    {
        $model = is_string($model) ? $model : get_class($model);

        return array_key_exists($model, $this->modelSchemas);
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
