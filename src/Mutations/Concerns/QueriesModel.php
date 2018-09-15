<?php

namespace Bakery\Mutations\Concerns;

use Illuminate\Database\Eloquent\Model;
use GraphQL\Type\Definition\ResolveInfo;
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
    protected $schema;

    /**
     * Get the model based on the arguments provided.
     *
     * @param mixed $root
     * @param array $args
     * @param mixed $context
     * @param \GraphQL\Type\Definition\ResolveInfo $info
     * @return mixed
     */
    public function find($root, array $args, $context, ResolveInfo $info)
    {
        $primaryKey = $this->model->getKeyName();

        $query = $this->schema->getQuery();

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
     * @param mixed $context
     * @param \GraphQL\Type\Definition\ResolveInfo $info
     * @return Model
     */
    public function findOrFail($root, array $args, $context, ResolveInfo $info): Model
    {
        $result = $this->find($root, $args, $context, $info);

        if (! $result) {
            throw (new ModelNotFoundException)->setModel(class_basename($this->model));
        }

        return $result;
    }
}
