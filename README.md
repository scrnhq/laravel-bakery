![Bakery](artwork.png)

[![Build Status](https://travis-ci.org/scrnhq/laravel-bakery.svg?branch=master)](https://travis-ci.org/scrnhq/laravel-bakery)
[![Latest Stable Version](https://poser.pugx.org/scrnhq/laravel-bakery/version)](https://packagist.org/packages/scrnhq/laravel-bakery)
[![Total Downloads](https://poser.pugx.org/scrnhq/laravel-bakery/downloads)](https://packagist.org/packages/scrnhq/laravel-bakery)
[![codecov](https://codecov.io/gh/scrnhq/laravel-bakery/branch/master/graph/badge.svg)](https://codecov.io/gh/scrnhq/laravel-bakery)
[![StyleCI](https://github.styleci.io/repos/109427894/shield?style=flat)](https://github.styleci.io/repos/109427894)
[![Maintainability](https://api.codeclimate.com/v1/badges/de462571125eb6bf7af2/maintainability)](https://codeclimate.com/github/scrnhq/laravel-bakery/maintainability)

An on-the-fly GraphQL Schema generator from Eloquent models for Laravel.

- [Version Compatibility](#version-compatibility)
- [Installation](#installation)
- [Quickstart](#quickstart)
- [Model schemas](#model-schemas)

## Version Compatibility

| Laravel | Bakery |
| :------ | :----- |
| 5.4.x   | 1.0.x  |
| 5.5.x   | 1.0.x  |
| 5.6.x   | 2.0.x  |

## Installation

Install via composer:

```
composer require scrnhq/laravel-bakery
```

or require in `composer.json`:

```json
{
    "require": {
        "scrnhq/laravel-bakery": "^2.0"
    }
}
```

then run `composer update` in your terminal to install Bakery.

## Quickstart

After installing Bakery, publish the configuration and asserts using the `bakery:install` Artisan command.

```
php artisan bakery:install
```

After running this command, the configuration file should be located at `config/bakery.php`. The default
`App\Bakery\User` Bakery model schema refers to the `App\User` model.

You can find your new GraphQL API at `/graphql` and you can navigate to `/graphql/explore` to find GraphiQL, the
graphical interactive GraphQL IDE.

```gql
query {
  users {
    items {
      id
    }
  }
}
```

## Model schemas

By default, Bakery model schema's are stored in the `app\Bakery` directory. You can generate a new model schema using
the handy `bakery:modelschema` Artisan command.

```
php artisan bakery:modelschema Post
```

The `model` property of a model schema defines which Eloquent model it corresponds to.

```php
/**
 * The model the schema corresponds to.
 *
 * @var string
 */
protected $model = \App\Post::class;
```

### Registering model schemas

> All model schema's in the `app/Bakery` directory will automatically be registered by Bakery. If you choose to 
> store your model schema's differently, you need to define and register your schema manually.

**You are not required to manually define and register a Schema. You can skip this step if you do not wish to
manually register a schema.**

In order to make model schemas available within GraphQL, they must be registered in a Schema. First you must create
a new `Schema` class. Next, you should set the `schema` item in the `config/bakery.php` file to the newly created
Schema.

There are two ways to manually registering model schemas in Bakery. You can use the `modelsIn` method in the schema
to load all models schemas in a given directory, or you can manually return an array of models schemas.
 
```php
namespace App\Support;

use Bakery\Support\Schema as BaseSchema;

class Schema extends BaseSchema
{
    /*
     * Get the models for the schema.
     *
     * @return array
     */
    public function models()
    {
        return $this->modelsIn(app_path('Bakery'));
        
        // Or, manually.
        return [
            App\Bakery\User::class,
            App\Bakery\Post::class,
        ];
    }
}
```

Now that you have created and registered your model schemas with Bakery, you can browse to `/graphql/explore` and query
your models in the interactive playground GraphQL.

```gql
query {
  posts {
    items {
      id
    }
  }
}
```

If everything is set up properly you will get a collection of posts in your database. You can also use GraphQL to
retrieve a single post.

```gql
query {
  posts(id: "1") {
    id
  }
}
```

Just like Laravel, Bakery follows naming conventions. It uses Laravel's pluralization library to transform your model
into queries so you can fetch an individual Post with `post` and a collection of Posts with `posts`.

### Fields

Now, each Bakery model schema contains a `fields` that return an array of fields, which extend the
`\Bakery\Fields\Field` class. To add a field to model schema, simply add it to `fields` method, where the key of
the item must match the name of the model `attribute`.

```php
use Bakery\Field;

/**
 * Get the fields for the schema.
 *
 * @return array
 */
public function fields(): array
{
    return [
        'title' => Field::string(),
    ];
}
```

Now you can query the title of the posts in GraphQL.

```gql
query {
  post(id: "1") {
    id
    title
  }
}
```

#### Field Types

Bakery has the following fields available:

- [Boolean](#boolean)
- [Float](#float)
- [ID](#id)
- [Int](#int)
- [String](#string)

##### Boolean
```php
Field::boolean()
```

##### Float
```php
Field::float()
```

##### ID
```php
Field::ID()
```

##### Int
```php
Field::int()
```

##### String
```php
Field::string()
```

### Relations

In addition to the fields described above, Bakery supports Eloquent relationships, too. To add a relationship to the
model schema, simply add it to the `relations` method, where the key of the item must match the relation name. Let's
say a `User` model `hasMany` `Post` models. Then you would define your Bakery model schema's like so:

`app\Bakery\User.php`

```php
use Bakery\Field;
use App\Bakery\Post;

/**
 * Get the fields for the schema.
 *
 * @return array
 */
public function relations()
{
    return [
        'posts' => Field::collection(Post::class),
    ];
}
```

The inverse of the previous relation is that a `Post` model `belongsTo` a `User` model. The Bakery model schema
would be defined like so:

`app\Bakery\Post.php`

```php
use Bakery\Field;
use App\Bakery\User;

/**
 * Get the fields for the schema.
 *
 * @return array
 */
public function relations()
{
    return [
        'user' => Field::model(User::class),
    ];
}
```

This way you can get all posts related to a user within a single GraphQL query.

```gql
query {
  user(id: "1") {
    id
    posts {
      id
    }
  }
}
```

### Mutations

Another key feature of GraphQL that Bakery fully supports are mutations. Bakery automatically creates the `create`,
`update`, and `delete` mutations for each registered model. Bakery also seamlessly uses Laravel's policies to
authorize the actions of your users.

> Having policies for your models is required for Bakery mutations to work. See 
> https://laravel.com/docs/5.6/authorization for more information.

For example, with the model schemas mentioned above, you could create a `Post` with a simple GraphQL mutation.

```gql
mutation {
  createPost(input: {
    title: "Hello world!"
  }) {
    id
  }
}
```
