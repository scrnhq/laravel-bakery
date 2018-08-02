<?php

namespace Bakery;

use GraphQL\GraphQL;
use Bakery\Utils\Utils;
use GraphQL\Type\Schema;
use Bakery\Traits\BakeryTypes;
use Bakery\Exceptions\TypeNotFound;
use GraphQL\Executor\ExecutionResult;
use GraphQL\Type\Definition\ObjectType;
use Illuminate\Database\Eloquent\Model;
use Bakery\Support\Schema as BakerySchema;

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
     * @param $class
     * @param string|null $name
     */
    public function addType($class, string $name = null)
    {
        $name = $name ?: $class->name;
        $this->types[$name] = $class;
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
     * Return if the model has a model schema in Bakery.
     * This can either be an instance or a class name.
     *
     * @param mixed $model
     * @return mixed
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
     * Return the types that should be included in all schemas.
     *
     * @return array
     */
    public function getStandardTypes()
    {
        return [
            new Types\PaginationType(),
            new Types\OrderType(),
        ];
    }

    /**
     * Get the default GraphQL schema.
     *
     * @return Schema
     */
    public function schema()
    {
        $schema = new Support\DefaultSchema();

        return $schema->toGraphQLSchema();
    }

    /**
     * Get the GraphQL type.
     *
     * @param $name
     * @return ObjectType
     * @throws TypeNotFound
     */
    public function getType($name)
    {
        if (! isset($this->types[$name])) {
            throw new TypeNotFound('Type '.$name.' not found.');
        }

        if (isset($this->typeInstances[$name])) {
            return $this->typeInstances[$name];
        }

        $class = $this->types[$name];
        $type = $this->makeObjectType($class, ['name' => $name]);
        $this->typeInstances[$name] = $type;

        return $type;
    }

    /**
     * Get the GraphQL type, alias for getType().
     *
     * @api
     * @param $name
     * @return ObjectType
     * @throws TypeNotFound
     */
    public function type($name)
    {
        return $this->getType($name);
    }

    /**
     * Execute the GraphQL query.
     *
     * @param array $input
     * @param Schema|BakerySchema $schema
     * @return ExecutionResult
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

    protected function makeObjectType($type, $options = [])
    {
        $objectType = null;
        if ($type instanceof \GraphQL\Type\Definition\Type) {
            $objectType = $type;
        } elseif (is_array($type)) {
            $objectType = $this->makeObjectTypeFromFields($type, $options);
        } else {
            $objectType = $type->toGraphQLType($options);
        }

        return $objectType;
    }

    protected function makeObjectTypeFromFields($fields, $options = [])
    {
        return new ObjectType(array_merge([
            'fields' => $fields,
        ], $options));
    }

    public function model(string $definition): BakeryField
    {
        return (new BakeryField($definition));
    }

    public function collection(string $definition): BakeryField
    {
        return $this->model($definition)->collection();
    }
}
