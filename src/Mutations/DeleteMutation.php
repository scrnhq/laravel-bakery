<?php

namespace Bakery\Mutations;

use GraphQL\Type\Definition\Type;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use Bakery\Exceptions\TooManyResultsException;
use Bakery\Support\Facades\Bakery;

class DeleteMutation extends EntityMutation
{
    /**
     * The action name used for building the Mutation name.
     *
     * @var string
     */
    protected $action = 'delete';

    /**
     * Get the return type of the mutation.
     *
     * @return Type
     */
    public function type()
    {
        return Bakery::boolean();
    }

    /**
     * Get the arguments of the mutation.
     *
     * @return array
     */
    public function args()
    {
        return $this->model->getLookupFields();
    }

    /**
     * Resolve the mutation.
     *
     * @param  mixed $root
     * @param  array $args
     * @return bool
     */
    public function resolve($root, $args = []): bool
    {
        $model = $this->getModel($args);
        $this->authorize('delete', $model);

        return $model->delete();
    }

    /**
     * Get the model for the mutation.
     *
     * @param array $args
     * @return Model
     */
    protected function getModel(array $args): Model
    {
        $primaryKey = $this->model->getKeyName();

        if (array_key_exists($primaryKey, $args)) {
            return $this->model->findOrFail($args[$primaryKey]);
        }

        $query = $this->model->query();

        foreach ($args as $key => $value) {
            $query->where($key, $value);
        }

        $results = $query->get();

        if ($results->count() < 1) {
            throw (new ModelNotFoundException)->setModel($this->class);
        }

        if ($results->count() > 1) {
            throw (new TooManyResultsException)->setModel($this->class, $results->pluck($this->model->getKeyName()));
        }

        return $results->first();
    }
}
