<?php

namespace Bakery;

use Auth;
use GraphQL\GraphQL;
use Bakery\Utils\Utils;
use GraphQL\Type\Schema;
use Bakery\Types\ModelType;
use Bakery\Types\EntityType;
use Bakery\Traits\BakeryTypes;
use Bakery\Eloquent\BakeryModel;
use Bakery\Exceptions\TypeNotFound;
use Bakery\Support\Schema as BakerySchema;
use GraphQL\Executor\ExecutionResult;
use GraphQL\Type\Definition\ObjectType;
use Illuminate\Database\Eloquent\Model;

class Bakery
{
    use BakeryTypes;

    /**
     * The schemas
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
     * The registered Bakery models.
     *
     * @var array
     */
    protected $bakeryModels = [];

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

    public function addModels(array $models)
    {
        foreach ($models as $key => $model) {
            $this->addModel($model);
        }
    }

    public function addModel($model)
    {
        $this->bakeryModels[resolve($model)->getModelClass()] = $model;
    }

    /**
     * Return if the name is registered as a type.
     *
     * @param string $name
     * @return boolean
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
        ];
    }

    /**
     * Get the default GraphQL schema
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
        if (!isset($this->types[$name])) {
            throw new TypeNotFound('Type ' . $name . ' not found.');
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
     */
    public function type($name)
    {
        return $this->getType($name);
    }

    /**
     * Get the bakery model by name or class name.
     *
     * @param string $name
     * @return ModelType
     */
    public function getModelType($name): BakeryModel
    {
        $name = is_string($name) ? $name : class_basename($name);

        if (!isset($this->types[$name])) {
            throw new TypeNotFound('Type ' . $name . ' not found.');
        }
        
        return $this->types[$name]->model;
    }

    /**
     * Wrap an Eloquent model in a Bakery model.
     *
     * @param Model $model
     * @return BakeryModel
     */
    public function getModel(Model $model): BakeryModel
    {
        Utils::invariant(
            array_key_exists(get_class($model), $this->bakeryModels),
            class_basename($model) . ' is not registered as Bakery model'
        );

        return new $this->bakeryModels[get_class($model)]($model);
    }

    /**
     * Wrap a new Eloquent model in a Bakery model.
     *
     * @param Model $model
     * @return BakeryModel
     */
    public function newModel(Model $model): BakeryModel
    {
        Utils::invariant(
            array_key_exists(get_class($model), $this->bakeryModels),
            class_basename($model) . ' is not registered as Bakery model'
        );

        return new $this->bakeryModels[get_class($model)]();
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
        if (!$schema) {
            $schema = $this->schema();
        } elseif ($schema instanceof BakerySchema) {
            $schema = $schema->toGraphQLSchema();
        }

        $root = null;
        $context = Auth::user();
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
}
