<?php

namespace Scrn\Bakery\Http\Controller;

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
    public function query(Request $request): JsonResponse
    {
        $input = $request->all();

        $data = app('bakery')->executeQuery($input);

        return response()->json($data, 200, []);
    }
}
