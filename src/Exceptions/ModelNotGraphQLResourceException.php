<?php

namespace Scrn\Bakery\Exceptions;

use RuntimeException;

class ModelNotGraphQLResourceException extends RuntimeException
{
    /**
     * Name of the affected Eloquent model.
     *
     * @var string
     */
    protected $model;

    /**
     * Set the affected Eloquent model and instance ids.
     *
     * @param  string  $model
     * @param  int|array  $ids
     * @return $this
     */
    public function setModel($model, $ids = [])
    {
        $this->model = $model;

        $this->message = "You are trying to register {$model} as a GraphQL resource, but it does not have the GraphQLResource trait.";

        return $this;
    }

    /**
     * Get the affected Eloquent model.
     *
     * @return string
     */
    public function getModel()
    {
        return $this->model;
    }
}
