<?php

use Illuminate\Support\Facades\Route;

Route::namespace('Bakery\Http\Controllers')
    ->domain(config('bakery.domain', null))
    ->middleware(config('bakery.middleware', []))
    ->as('bakery.')
    ->prefix(config('bakery.path'))
    ->group(function () {
        $controller = config('bakery.controller', 'BakeryController@graphql');

        Route::get('/', $controller)->name('graphql');
        Route::post('/', $controller)->name('graphql');

        if (config('bakery.graphiql')) {
            Route::get('/explore', 'BakeryController@graphiql')->name('explore');
        }
    });
