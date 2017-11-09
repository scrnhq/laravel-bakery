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
    'route' => '/graphql',

    /*
     |--------------------------------------------------------------------------
     | Bakery Controller
     |--------------------------------------------------------------------------
     |
     | Here you can define the controller that should
     | handle the GraphQL requests. 
     */
    'controller' => '\Scrn\Bakery\Http\Controller\BakeryController@query',
];
