<?php

namespace Scrn\Bakery;

use Exception;
use Scrn\Bakery\Exceptions\ModelNotRegistered;

class Bakery
{
    protected $models = [];

    public function addModel($class)
    {
        $this->models[] = $class;
        return $this;
    }

    /**
     * Get the entity type related to the model
     *
     * @param $model
     * @return
     * @throws Exception
     */
    public function entityType($model)
    {
        if (!in_array($model, $this->models)) {
            throw new ModelNotRegistered('Model '.$model.' not found.');
        }

        return app($model)->toObjectType();
    }
}
