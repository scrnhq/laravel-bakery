<?php

namespace Bakery\Mutations;

use Illuminate\Database\Eloquent\Model;

class CreateMutation extends EntityMutation
{
    /**
     * Get the name of the mutation.
     *
     * @return string
     */
    protected function name(): string
    {
        if (property_exists($this, 'name')) {
            return $this->name;
        }
        
        return 'create'.$this->schema->typename();
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
        $this->authorize('create', $this->model);

        $input = $args['input'];
        $model = $this->model->createWithInput($input);

        return $model;
    }
}
