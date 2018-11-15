<?php

namespace Bakery\Support;

use Bakery\Utils\Utils;

class DefaultSchema extends Schema
{
    /**
     * Get the models from the config.
     *
     * @return array|\Illuminate\Config\Repository|mixed
     */
    public function models(): array
    {
        $models = $this->modelsIn(app_path('Bakery'));

        Utils::invariant(count($models) > 0, 'There must be model schema\'s defined in the Bakery directory.');

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
