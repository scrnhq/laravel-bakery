<?php

namespace Scrn\Bakery;

use GraphQL\Executor\ExecutionResult;
use GraphQL\GraphQL;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use Scrn\Bakery\Exceptions\ModelNotRegistered;
use Scrn\Bakery\Exceptions\TypeNotFound;
use Scrn\Bakery\Queries\CollectionQuery;
use Scrn\Bakery\Queries\EntityQuery;

class Bakery
{
    /**
     * The registered models.
     *
     * @var array
     */
    protected $models = [];

    /**
     * The registered types.
     *
     * @var array
     */
    protected $types = [];

    /**
     * The type instances.
     *
     * @var array
     */
    protected $typeInstances = [];

    /**
     * The queries.
     *
     * @var array
     */
    protected $queries = [];

    public function addModel($class)
    {
        $this->models[] = $class;
        $this->registerEntityType($class);
        $this->registerEntityQuery($class);
        $this->registerCollectionQuery($class);
        return $this;
    }

    public function getModels()
    {
        return $this->models;
    }

    public function getQueries()
    {
        return $this->queries;
    }

    /**
     * Format a class name to the name for the entity query.
     *
     * @param string $class
     * @return string
     */
    protected function formatEntityName(string $class): string
    {
        return camel_case(str_singular(class_basename($class)));
    }

    /**
     * Format a class name to the name for the collection query.
     *
     * @param string $class
     * @return string
     */
    protected function formatCollectionName(string $class): string
    {
        return camel_case(str_plural(class_basename($class)));
    }

    protected function registerEntityQuery($class)
    {
        $name = $this->formatEntityName($class);
        $this->queries[$name] = new EntityQuery($class, $name);
    }

    protected function registerCollectionQuery($class)
    {
        $name = $this->formatCollectionName($class);
        $this->queries[$name] = new CollectionQuery($class, $name);
    }

    protected function registerEntityType($class)
    {
        $entityType = new EntityType($class);
        $this->types[$entityType->name] = $entityType;
    }

    /**
     * Get the GraphQL schema
     *
     * @return Schema
     */
    public function schema()
    {
        $types = [];
        foreach ($this->models as $model) {
            $objectType = $this->makeObjectType(new EntityType($model));
            $this->typeInstances[$objectType->name] = $objectType;
            $types[] = $objectType;
        }

        $query = $this->makeObjectType(['query' => Type::boolean()], [
            'name' => 'Query',
        ]);

        $mutation = $this->makeObjectType(['mutation' => Type::boolean()], [
            'name' => 'Mutation',
        ]);

        return new Schema([
            'query' => $query,
            'mutation' => $mutation,
            'subscription' => null,
            'types' => $types,
        ]);
    }

    public function getType($name)
    {
        if (!isset($this->types[$name])) {
            throw new TypeNotFound('Type ' . $name . ' not found.');
        }

        if (isset($this->typeInstances[$name])) {
            return $this->typeInstances[$name];
        }

        $class = $this->types[$name];
        $type = $this->makeObjectType($class);
        $this->typeInstances[$name] = $type;

        return $type;
    }

    public function getEntityType($class, $fresh = false)
    {
        if (!in_array($class, $this->models)) {
            throw new ModelNotRegistered('Model ' . $class . ' not registered.');
        }

        $model = app($class);

        $typeName = class_basename($model);

        if (!$fresh && isset($this->typeInstances[$typeName])) {
            return $this->typeInstances[$typeName];
        }

        $type = $this->makeObjectType(
            $model, [
            'name' => $typeName,
        ]);
        $this->typeInstances[$typeName] = $type;

        return $type;
    }


    public function makeObjectType($type, $options = [])
    {
        $objectType = null;
        if ($type instanceof ObjectType) {
            $objectType = $type;
            foreach ($options as $key => $value) {
                $objectType->{$key} = $value;
            }
        } elseif (is_array($type)) {
            $objectType = $this->makeObjectTypeFromFields($type, $options);
        } else {
            $objectType = $this->makeObjectTypeFromClass($type, $options);
        }

        return $objectType;
    }

    protected function makeObjectTypeFromFields($fields, $options = [])
    {
        return new ObjectType(array_merge([
            'fields' => $fields,
        ], $options));
    }

    protected function makeObjectTypeFromClass($class, $options = [])
    {
        return $class->toType();
    }

    /**
     * Execute the GraphQL query.
     *
     * @param array $input
     * @return ExecutionResult
     */
    public function executeQuery($input): ExecutionResult
    {
        $schema = $this->schema();

        $root = null;
        $context = null;
        $query = array_get($input, 'query');
        $variables = json_decode(array_get($input, 'variables'));
        $operationName = array_get($input, 'operationName');

        return GraphQL::executeQuery($schema, $query, $root, $context, $variables, $operationName);
    }
}
