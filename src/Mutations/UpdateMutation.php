<?php

namespace Bakery\Mutations;

use Bakery\Utils\Utils;
use Bakery\Eloquent\BakeryModel;
use Bakery\Support\Facades\Bakery;
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
    public function args()
    {
        return array_merge(
            parent::args(),
            Utils::nullifyFields($this->model->getLookupFields())->toArray()
        );
    }

    /**
     * Resolve the mutation.
     *
     * @param  mixed $root
     * @param  array $args
     * @return array
     */
    public function resolve($root, $args = []): Model
    {
        // TODO: Naming is a bit eh, weird here.
        $model = $this->getModel($args);
        $this->authorize($this->action, $model->getModel());

        $input = $args['input'];
        $model->updateWithGraphQLInput($input);

        return $model->getModel();
    }

    /**
     * Get the model for the mutation.
     *
     * @param array $args
     * @return Model
     */
    protected function getModel(array $args): BakeryModel
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

        return Bakery::getModel($results->first());
    }
}
