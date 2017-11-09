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
        $this->queries[] = $this->createEntityQuery($class);
        $this->queries[] = $this->createCollectionQuery($class);
        return $this;
    }

    public function getQueries()
    {
        return $this->queries;
    }

    protected function createEntityQuery($class)
    {
        return new EntityQuery($class); 
    }

    protected function createCollectionQuery($class)
    {
        return new CollectionQuery($class); 
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
