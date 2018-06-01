<?php

namespace Bakery\Mutations;

use Bakery\Utils\Utils;
use Illuminate\Database\Eloquent\Model;
use Bakery\Exceptions\TooManyResultsException;
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
    public function args(): array
    {
        return array_merge(
            parent::args(),
            Utils::nullifyFields($this->schema->getLookupFields())->toArray()
        );
    }

    /**
     * Resolve the mutation.
     *
     * @param  mixed $root
     * @param  array $args
     * @param  mixed $viewer
     * @return Model
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function resolve($root, array $args, $viewer): Model
    {
        $model = $this->getModel($args);
        $this->authorize($this->action, $model);

        $input = $args['input'];
        $model->updateWithInput($input);

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
