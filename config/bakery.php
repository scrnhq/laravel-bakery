<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Bakery Path
    |--------------------------------------------------------------------------
    |
    | This configuration option determines the path where GraphQL will be
    | accessible from. Setting this to null will disable the default
    | exposure of routes.
    |
    */

    'path' => '/graphql',

    /*
    |--------------------------------------------------------------------------
    | Bakery Domain
    |--------------------------------------------------------------------------
    |
    | This configuration option determines the domain where GraphQL will be
    | accessible from.
    |
    */

    'domain' => null,

    /*
    |--------------------------------------------------------------------------
    | Bakery GraphiQL
    |--------------------------------------------------------------------------
    |
    | This configuration option determines if GraphiQL should be enabled.
    |
    | https://github.com/graphql/graphiql
    |
     */

    'graphiql' => true,

    /*
    |--------------------------------------------------------------------------
    | Bakery Route Middleware
    |--------------------------------------------------------------------------
    |
    | These middleware will be assigned to every Telescope route, giving you
    | the chance to add your own middleware to this list or change any of
    | the existing middleware. Or, you can simply stick with this list.
    |
    */

    'middleware' => [

    ],

    /*
    |--------------------------------------------------------------------------
    | Bakery Controller
    |--------------------------------------------------------------------------
    |
    | This configuration option determines the controller to be used for
    | GraphQL requests.
    |
    */

    'controller' => '\Bakery\Http\Controllers\BakeryController@graphql',

    /*
    |--------------------------------------------------------------------------
    | Bakery Schema
    |--------------------------------------------------------------------------
    |
    | This configuration option determines the schema to be used for
    | GraphQL requests.
    |
    */

    'schema' => \Bakery\Support\DefaultSchema::class,

    /*
    |--------------------------------------------------------------------------
    | Bakery PostgreSQL Full-text Search Dictionary
    |--------------------------------------------------------------------------
    |
    | This configuration option determines the dictionary to be used
    | when performing full-text search in collection queries.
    |
    */

    'postgresDictionary' => 'simple',

    'security' => [

        /*
        |--------------------------------------------------------------------------
        | Bakery GraphQL Introspection
        |--------------------------------------------------------------------------
        |
        | This configuration option determines if the GraphQL introspection
        | query will be allowed. Introspection is a mechanism for fetching
        | schema structure.
        |
        | http://webonyx.github.io/graphql-php/security/#disabling-introspection
        |
        */
        'disableIntrospection' => env('BAKERY_DISABLE_INTROSPECTION', false),

        /*
        |--------------------------------------------------------------------------
        | Bakery Pagination Max Count
        |--------------------------------------------------------------------------
        |
        | This configuration option determines the maximum amount of items that
        | can be requested in a single page for collection queries.
        |
        */
        'paginationMaxCount' => env('BAKERY_PAGINATION_MAX_COUNT', 1000),

        /*
        |--------------------------------------------------------------------------
        | Bakery Eager Loading Maximum Depth
        |--------------------------------------------------------------------------
        |
        | This configuration option determines the maximum depth of the query
        | that will allow eager relation loading.
        |
        */
        'eagerLoadingMaxDepth' => env('BAKERY_EAGER_LOADING_MAX_DEPTH', 5),
    ],
];
