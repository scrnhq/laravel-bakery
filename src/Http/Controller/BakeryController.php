<?php

namespace Scrn\Bakery\Http\Controller;

use GraphQL\GraphQL;
use GraphQL\Type\Schema;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Http\JsonResponse;
use GraphQL\Executor\ExecutionResult;
use GraphQL\Type\Definition\ObjectType;

class BakeryController extends Controller
{
    /**
     * Construct the GraphQL controller.
     *
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        //
    }

    /**
     * Handle an HTTP response containing the GraphQL query.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function query(Request $request): JsonResponse
    {
        $input = $request->all();

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
        $data = $this->executeQuery($schema, $input);

        return response()->json($data, 200, []);
    }

    /**
     * Execute the GraphQL query and return the result.
     *
     * @param Schema $schema
     * @param array $input
     * @return ExecutionResult
     */
    protected function executeQuery(Schema $schema, $input = []): ExecutionResult
    {
        $root = null;
        $context = null;
        $query = array_get($input, 'query');
        $variables = json_decode(array_get($input, 'variables'));
        $operationName = array_get($input, 'operationName');

        return GraphQL::executeQuery($schema, $query, $root, $context, $variables, $operationName);
    }
}