<?php

namespace Scrn\Bakery;

use Exception;
use GraphQL\GraphQL;
use GraphQL\Type\Schema;
use GraphQL\Executor\ExecutionResult;
use GraphQL\Type\Definition\ObjectType;

use Scrn\Bakery\Queries\EntityQuery;
use Scrn\Bakery\Queries\CollectionQuery;
use Scrn\Bakery\Exceptions\ModelNotRegistered;

class Bakery
{
    protected $models = [];

    protected $queries = [];

    public function addModel($class)
    {
        $this->models[] = $class;
        $this->registerEntityQuery($class);
        $this->registerCollectionQuery($class);
        return $this;
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

    /**
     * Get the entity type related to the model
     *
     * @param $model
     * @return
     * @throws Exception
     */
    public function entityType($model)
    {
        if (!in_array($model, $this->models)) {
            throw new ModelNotRegistered('Model '.$model.' not found.');
        }

        return app($model)->toObjectType();
    }

    /**
     * Execute the GraphQL query.
     *
     * @param array $input
     * @return ExecutionResult
     */
    public function executeQuery($input): ExecutionResult
    {
        $schema = new Schema([
            'query' => new ObjectType([
                'name' => 'Query',
                'fields' => $this->getQueries(),
            ]),
            'mutation' => null, 
        ]);

        $root = null;
        $context = null;
        $query = array_get($input, 'query');
        $variables = json_decode(array_get($input, 'variables'));
        $operationName = array_get($input, 'operationName');

        return GraphQL::executeQuery($schema, $query, $root, $context, $variables, $operationName);
    }
}
