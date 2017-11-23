<?php

return [
    /*
     |--------------------------------------------------------------------------
     | Bakery Models
     |--------------------------------------------------------------------------
     |
     | Here you can define the models that should be baked by Bakery.
     */
    'models' => [],

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
];
