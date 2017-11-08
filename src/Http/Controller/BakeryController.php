<?php

namespace Scrn\Bakery\Http\Controller;

use GraphQL\GraphQL;
use GraphQL\Type\Schema;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Http\JsonResponse;

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

        $data = app('bakery')->executeQuery($input);

        return response()->json($data, 200, []);
    }
}