<?php

namespace Bakery\Mutations;

use Bakery\Exceptions\TooManyResultsException;
use GraphQL\Type\Definition\Type;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UpdateMutation extends EntityMutation
{
    /**
     * The action name used for building the Mutation name.
     *
     * @var string
     */
    protected $action = 'update';

    /**
     * Get the arguments of the mutation.
     *
     * @return array
     */
    public function args()
    {
        return array_merge(parent::args(), [
            $this->model->getKeyName() => Type::ID(),
        ], $this->model->lookupFields());
    }

    /**
     * Resolve the mutation.
     *
     * @param  mixed $root
     * @param  array $args
     * @return Model
     */
    public function resolve($root, $args = []): Model
    {
        $model = $this->getModel($args);
        $this->authorize($this->action, $model);

        $input = $args['input'];
        $model->updateWithGraphQLInput($input);

        return $model;
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
        $fields = array_except($args, ['input']);

        foreach ($fields as $key => $value) {
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
