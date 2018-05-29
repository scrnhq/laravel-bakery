<?php

namespace Bakery\Mutations;

use Illuminate\Database\Eloquent\Model;

class CreateMutation extends EntityMutation
{
    /**
     * The action name used for building the Mutation name.
     *
     * @var string
     */
    protected $action = 'create';

    /**
     * Resolve the mutation.
     *
     * @param  mixed $root
     * @param  array $args
     * @return array
     */
    public function resolve($root, $args = []): Model
    {
        $this->authorize($this->action, $this->model->getModel());

        $input = $args['input'];
        $model = $this->model->createWithGraphQLInput($input);
        return $model;
    }
}
