<?php

namespace Bakery\Http\Controllers;

use Bakery\Bakery;
use GraphQL\Error\Debug;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Contracts\Debug\ExceptionHandler;

class BakeryController extends Controller
{
    protected function isExceptionHandlingDisabled()
    {
        $handler = app(ExceptionHandler::class);

        return str_contains(get_class($handler), 'InteractsWithExceptionHandling');
    }

    protected function debug()
    {
        $debug = null;

        if (config('app.debug') or app()->runningUnitTests()) {
            $debug = Debug::INCLUDE_DEBUG_MESSAGE;
        }

        if ($this->isExceptionHandlingDisabled()) {
            $debug = Debug::RETHROW_INTERNAL_EXCEPTIONS;
        }

        return $debug;
    }

    /**
     * Handle an HTTP response containing the GraphQL query.
     *
     * @param Request $request
     * @param \Bakery\Bakery $bakery
     * @return JsonResponse
     * @throws \Exception
     */
    public function graphql(Request $request, Bakery $bakery): JsonResponse
    {
        $input = $request->all();

        $data = $bakery->executeQuery($input)->toArray($this->debug());

        return response()->json($data, 200, []);
    }

    /**
     * Serve the GraphiQL tool.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Bakery\Bakery $bakery
     * @return \Illuminate\Contracts\View\View
     */
    public function graphiql(Request $request, Bakery $bakery)
    {
        if (! app()->isLocal()) {
            abort(404);
        }

        return $bakery->graphiql('bakery.graphql');
    }
}
