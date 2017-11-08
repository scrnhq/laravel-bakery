<?php

namespace Scrn\Bakery;

use Exception;
use GraphQL\GraphQL;
use GraphQL\Type\Schema;
use GraphQL\Executor\ExecutionResult;
use GraphQL\Type\Definition\ObjectType;
use Scrn\Bakery\Exceptions\ModelNotRegistered;

class Bakery
{
    protected $models = [];

    public function addModel($class)
    {
        $this->models[] = $class;
        return $this;
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
                'fields' => [],
            ]),
            'mutation' => new ObjectType([
                'name' => 'Mutation',
                'fields' => [],
            ]),
        ]);

        $root = null;
        $context = null;
        $query = array_get($input, 'query');
        $variables = json_decode(array_get($input, 'variables'));
        $operationName = array_get($input, 'operationName');

        return GraphQL::executeQuery($schema, $query, $root, $context, $variables, $operationName);
    }
}
