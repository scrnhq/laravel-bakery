<?php

namespace Bakery\Http\Controller;

use App;
use GraphQL\Error\Debug;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

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
    public function graphql(Request $request): JsonResponse
    {
        $input = $request->all();

        $debug = Debug::INCLUDE_DEBUG_MESSAGE | Debug::RETHROW_INTERNAL_EXCEPTIONS;
        $data = app('bakery')->executeQuery($input)->toArray($debug);

        return response()->json($data, 200, []);
    }

    public function graphiql()
    {
        if (!App::isLocal()) {
            abort(404);
        }

        return app('bakery')->graphiql('graphql');
    }
}
