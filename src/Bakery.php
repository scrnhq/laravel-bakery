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
use Bakery\Types\Definitions\ObjectType;
use Bakery\Support\Schema as BakerySchema;
use Bakery\Types\Definitions\ReferenceType;
use GraphQL\Type\Definition\Type as GraphQLType;

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
     * @param array $models
     * @return void
     */
    public function addModelSchemas(array $models)
    {
        foreach ($models as $model) {
            $this->addModelSchema($model);
        }
    }

    /**
     * Add a single model schema to the registry.
     *
     * @param mixed $model
     * @return void
     */
    public function addModelSchema($model)
    {
        if (! is_subclass_of($model, Model::class)) {
            $this->modelSchemas[$model::$model] = $model;
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

    public function definition($model)
    {
        return resolve($this->getModelSchema($model));
    }

    /**
     * Resolve the type of a definition of a model.
     *
     * @param $model
     * @return \GraphQL\Type\Definition\Type
     * @throws \Bakery\Exceptions\TypeNotFound
     */
    public function resolveDefinitionType($model): GraphQLType
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
     * Get the GraphQL type.
     *
     * @param $name
     * @return \Bakery\Types\Definitions\ReferenceType
     * @throws \Bakery\Exceptions\TypeNotFound
     */
    public function getType(string $name): ReferenceType
    {
        return new ReferenceType($this->resolve($name));
    }

    /**
     * Get the GraphQL type, alias for getType().
     *
     * @api
     * @param $name
     * @return \Bakery\Types\Definitions\ReferenceType
     * @throws \Bakery\Exceptions\TypeNotFound
     */
    public function type(string $name): ReferenceType
    {
        return $this->getType($name);
    }

    /**
     * Resolve a type from the registry.
     *
     * @param $name
     * @return \GraphQL\Type\Definition\Type
     * @throws \Bakery\Exceptions\TypeNotFound
     */
    public function resolve(string $name): GraphQLType
    {
        $type = null;

        if (isset($this->types[$name])) {
            $type = $this->types[$name];
        } elseif (class_exists($name)) {
            $instance = resolve($name);

            if ($instance instanceof Type) {
                $name = $instance->name();

                return $this->resolve($name);
            }
        }

        if (! $type) {
            throw new TypeNotFound('Type '.$name.' not found.');
        }

        if (isset($this->typeInstances[$name])) {
            return $this->typeInstances[$name];
        }

        $type = $this->makeObjectType($type, ['name' => $name]);
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

    public function graphiql($route, $headers = [])
    {
        return view(
            'bakery::graphiql',
            ['endpoint' => route($route), 'headers' => $headers]
        );
    }

    protected function makeObjectType(Type $type)
    {
        return $type->toType();
    }

    protected function makeObjectTypeFromFields($fields, $options = [])
    {
        return new ObjectType(array_merge([
            'fields' => $fields,
        ], $options));
    }
}
