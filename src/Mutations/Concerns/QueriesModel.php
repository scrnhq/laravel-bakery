<?php

namespace Bakery\Mutations\Concerns;

use Illuminate\Support\Arr;
use Bakery\Support\Arguments;
use Illuminate\Database\Eloquent\Model;
use Bakery\Exceptions\TooManyResultsException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

trait QueriesModel
{
    /**
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $model;

    /**
     * @var \Bakery\Eloquent\ModelSchema
     */
    protected $modelSchema;

    /**
     * Get the model based on the arguments provided.
     *
     * @param Arguments $args
     * @return mixed
     */
    public function find(Arguments $args)
    {
        $primaryKey = $this->model->getKeyName();

        $query = $this->modelSchema->getQuery();

        if (isset($args->{$primaryKey})) {
            return $query->find($args[$primaryKey]);
        }

        $fields = Arr::except($args->toArray(), ['input']);

        foreach ($fields as $key => $value) {
            $query->where($key, $value);
        }

        $results = $query->get();

        if ($results->count() > 1) {
            throw (new TooManyResultsException)->setModel(get_class($this->model),
                Arr::pluck($results, $this->model->getKeyName()));
        }

        return $results->first();
    }

    /**
     * Get the model based on the arguments provided.
     * Otherwise fail.
     *
     * @param Arguments $args
     * @return Model
     */
    public function findOrFail(Arguments $args): Model
    {
        $result = $this->find($args);

        if (! $result) {
            throw (new ModelNotFoundException)->setModel(class_basename($this->model));
        }

        return $result;
    }
}
