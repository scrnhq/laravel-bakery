<?php

namespace Bakery;

use Bakery\Support\Schema as BakerySchema;
use GraphQL\Executor\ExecutionResult;
use GraphQL\GraphQL;
use GraphQL\Type\Schema;
use Illuminate\Support\Arr;

class Bakery
{
    /**
     * Get the default GraphQL schema.
     *
     * @return \GraphQL\Type\Schema
     * @throws \Exception
     */
    public function schema(): Schema
    {
        /** @var \Bakery\Support\Schema $schema */
        $schema = resolve(config('bakery.schema', Support\DefaultSchema::class));

        return $schema->toGraphQLSchema();
    }

    /**
     * Execute the GraphQL query.
     *
     * @param array $input
     * @param \GraphQL\Type\Schema|\Bakery\Support\Schema $schema
     * @return \GraphQL\Executor\ExecutionResult
     * @throws \Exception
     */
    public function executeQuery($input, $schema = null): ExecutionResult
    {
        if (! $schema) {
            $schema = $this->schema();
        } elseif ($schema instanceof BakerySchema) {
            $schema = $schema->toGraphQLSchema();
        }

        $root = null;
        $context = [];
        $query = Arr::get($input, 'query');
        $variables = Arr::get($input, 'variables');
        if (is_string($variables)) {
            $variables = json_decode($variables, true);
        }
        $operationName = Arr::get($input, 'operationName');

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
