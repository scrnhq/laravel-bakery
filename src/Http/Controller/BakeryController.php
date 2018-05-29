<?php

namespace Bakery\Http\Controller;

use App;
use GraphQL\Error\Debug;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Contracts\Debug\ExceptionHandler;

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

    protected function isExceptionHandlingDisabled()
    {
        $handler = app(ExceptionHandler::class);

        return str_contains(get_class($handler), 'InteractsWithExceptionHandling');
    }

    protected function debug()
    {
        $debug = null;

        if (config('app.debug') or App::runningUnitTests()) {
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
    * @return JsonResponse
     */
    public function graphql(Request $request): JsonResponse
    {
        $input = $request->all();

        $data = app('bakery')->executeQuery($input)->toArray($this->debug());

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
