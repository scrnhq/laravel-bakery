<?php

namespace Bakery\Mutations\Concerns;

use Illuminate\Database\Eloquent\Model;
use Bakery\Exceptions\TooManyResultsException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

trait QueriesModel
{
    /**
     * Get the model based on the arguments provided.
     *
     * @param mixed $root
     * @param array $args
     * @param mixed $viewer
     * @return mixed
     */
    public function find($root, array $args, $viewer)
    {
        $primaryKey = $this->model->getKeyName();

        $query = $this->schema->getBakeryQuery($viewer);

        if (array_key_exists($primaryKey, $args)) {
            return $query->find($args[$primaryKey]);
        }

        $fields = array_except($args, ['input']);

        foreach ($fields as $key => $value) {
            $query->where($key, $value);
        }

        $results = $query->get();

        if ($results->count() > 1) {
            throw (new TooManyResultsException)->setModel($this->class, $results->pluck($this->model->getKeyName()));
        }

        return $results->first();
    }

    /**
     * Get the model based on the arguments provided.
     * Otherwise fail.
     *
     * @param mixed $root
     * @param array $args
     * @param mixed $viewer
     * @return Model
     */
    public function findOrFail($root, array $args, $viewer): Model
    {
        $result = $this->find($root, $args, $viewer);

        if (! $result) {
            throw (new ModelNotFoundException)->setModel(class_basename($this->model));
        }

        return $result;
    }
}
