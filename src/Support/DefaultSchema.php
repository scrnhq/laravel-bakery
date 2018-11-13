<?php

namespace Bakery\Support;

use Bakery\Utils\Utils;
use Bakery\Eloquent\ModelSchema;
use Symfony\Component\Finder\Finder;

class DefaultSchema extends Schema
{
    /**
     * Get the models from the config.
     *
     * @return array|\Illuminate\Config\Repository|mixed
     */
    public function models(): array
    {
        $models = static::modelsIn(app_path('Bakery'));

        Utils::invariant(count($models) > 0, 'There must be model schema\'s defined in the Bakery directory.');

        return $models;
    }

    /**
     * Get the models in the given directory.
     *
     * @param  string $directory
     * @return array
     */
    public static function modelsIn($directory)
    {
        $namespace = app()->getNamespace();

        $models = [];

        foreach ((new Finder)->in($directory)->files() as $model) {
            $model = $namespace.str_replace(
                    ['/', '.php'],
                    ['\\', ''],
                    str_after($model->getPathname(), app_path().DIRECTORY_SEPARATOR)
                );

            if (is_subclass_of($model, ModelSchema::class)) {
                $models[] = $model;
            }
        }

        return $models;
    }

    /**
     * Get the types from the config.
     *
     * @return array
     */
    public function types(): array
    {
        return config('bakery.types') ?: [];
    }
}
