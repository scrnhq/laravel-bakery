# Model schemas

Model schemas are classes that lets you connect your Eloquent models with the GraphQL API. In there you can define which fields are available, which of them can be mutated and much more.

## Defining the model

The `model` property of a model schema defines which Eloquent model it corresponds to.

```php
/**
 * The model the schema corresponds to.
 *
 * @var string
 */
protected $model = \App\Post::class;
```

## Fields

Each Bakery model schema contains a `fields` that return an array of fields, which extend the
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

### Field Types

Bakery has the following fields available:

- [Boolean](#boolean)
- [Float](#float)
- [ID](#id)
- [Int](#int)
- [String](#string)
- [List](#list)

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

##### List
The list field accepts another field type as it's first argument.
```php
Field::list(Field::string())
```

## Relations

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

### Relation fields

Bakery has the following relation fields available:

- [Model](#model)
- [Collection](#collection)
- [Polymorphic](#polymorphic)

#### Model
Use the `model` relation field for a `hasOne` or `belongsTo` relationship, that returns a single model.
```php
Field::model(Post::class);
```

#### Collection
Use the `collection` relation field for a `hasMany` or `belongsToMany` relationship, that returns a collection of models.
```php
Field::collection(Post::class);
```

#### Polymorphic
Use the `polymorphic` relation field for a `morphMany` relationship, that returns a collection of models that can be of different types.
```php
Field::polymorphic([Post::class, Video::class]);
```

## Registering model schemas

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

## Creating model schemas

By default, Bakery model schema's are stored in the `app\Bakery` directory. You can generate a new model schema using
the handy `bakery:modelschema` Artisan command.

```
php artisan bakery:modelschema Post
```