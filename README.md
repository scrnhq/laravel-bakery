![Bakery](artwork.png)

[![Build Status](https://travis-ci.org/scrnhq/laravel-bakery.svg?branch=master)](https://travis-ci.org/scrnhq/laravel-bakery)
[![codecov](https://codecov.io/gh/scrnhq/laravel-bakery/branch/master/graph/badge.svg)](https://codecov.io/gh/scrnhq/laravel-bakery)
[![StyleCI](https://github.styleci.io/repos/109427894/shield?branch=feature/new-api)](https://github.styleci.io/repos/109427894)
[![Maintainability](https://api.codeclimate.com/v1/badges/de462571125eb6bf7af2/maintainability)](https://codeclimate.com/github/scrnhq/laravel-bakery/maintainability)

An on-the-fly GraphQL Schema generator from Eloquent models for Laravel.

## Getting started

_Docmentation is still in progress._

### Installation

Install via composer:

```
composer require scrnhq/laravel-bakery
```

or require in _composer.json_:

```js
{
    "require": {
        "scrnhq/laravel-bakery": "^1.0"
    }
}
```

then run `composer update` in your terminal to install Bakery.

Once this has finished, you will need to add the service provider to the providers array in your `app.php` config as follows:

**This package supports Laravel's package auto-discovery; if you are using Laravel 5.5 or above you can skip this step.**

```php
Bakery\BakeryServiceProvider::class,
```

### Setting it up

First publish the configuration file of Bakery by running the following command in your terminal:

```
php artisan vendor:publish --provider="Bakery\BakeryServiceProvider"
```

Now open up `config/bakery.php` and you will see a `model` property that contains an empty array. Here you can start adding the models you want Bakery to handle.

```php
return [
    'models' => [
        App\Models\User::class,
    ],
];
```

The models you define here should have two traits:

```php
<?php

namespace App;

use Bakery\Eloquent\Mutable;
use Bakery\Eloquent\Introspectable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
+   use Mutable;
+   use Notifiable;
    use Introspectable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];
}
```
