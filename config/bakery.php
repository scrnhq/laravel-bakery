<?php

return [
    /*
     |--------------------------------------------------------------------------
     | Bakery types
     |--------------------------------------------------------------------------
     |
     | Here you can define your Bakery types.
     */
    'types' => [],

    /*
     |--------------------------------------------------------------------------
     | Bakery Route
     |--------------------------------------------------------------------------
     |
     | Here you can define the route where Bakery should serve GraphQL.
     */
    'route' => 'graphql',

    'graphiqlRoute' => 'graphql/explore',

    /*
     |--------------------------------------------------------------------------
     | Bakery Controller
     |--------------------------------------------------------------------------
     |
     | Here you can define the controller that should
     | handle the GraphQL requests.
     */
    'controller' => '\Bakery\Http\Controller\BakeryController@query',

    /*
     |--------------------------------------------------------------------------
     | GraphiQL Controller
     |--------------------------------------------------------------------------
     |
     | Here you can define the controller that should
     | render the GraphiQL view.
     */
    'graphiqlController' => '\Bakery\Http\Controller\BakeryController@graphiql',

    'pagination' => [
        /*
        |--------------------------------------------------------------------------
        | Pagination max count
        |--------------------------------------------------------------------------
        |
        | Here you can define the maximum number of items that you can query
        | on a paginated collection.
        */
        'maxCount' => 1000,
    ],

    'postgresDictionary' => 'simple',
];
