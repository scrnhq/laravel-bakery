<?php

namespace Bakery\Exceptions;

use GraphQL\Error\UserError;
use Illuminate\Support\Arr;

class TooManyResultsException extends UserError
{
    /**
     * Name of the affected Eloquent model.
     *
     * @var string
     */
    protected $model;

    /**
     * The affected model IDs.
     *
     * @var array
     */
    protected $ids;

    /**
     * Set the affected Eloquent model and instance ids.
     *
     * @param  string $model
     * @param  array $ids
     * @return $this
     */
    public function setModel(string $model, array $ids = [])
    {
        $this->model = $model;
        $this->ids = Arr::wrap($ids);

        $this->message = "Too many results for model [{$model}]";

        return $this;
    }

    /**
     * Get the affected Eloquent model.
     *
     * @return string
     */
    public function getModel(): string
    {
        return $this->model;
    }

    /**
     * Get the affected Eloquent model IDs.
     *
     * @return array
     */
    public function getIds(): array
    {
        return $this->ids;
    }
}
