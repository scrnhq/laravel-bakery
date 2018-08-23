<?php

namespace Bakery\Mutations;

use Illuminate\Database\Eloquent\Model;

class UpdateMutation extends EntityMutation
{
    /**
     * Get the name of the mutation.
     *
     * @return string
     */
    public function name(): string
    {
        if (property_exists($this, 'name')) {
            return $this->name;
        }

        return 'update'.$this->schema->typename();
    }

    /**
     * Get the arguments of the mutation.
     *
     * @return array
     */
    public function args(): array
    {
        return array_merge(
            parent::args(),
            $this->schema->getLookupFields()->toArray()
        );
    }

    /**
     * Resolve the mutation.
     *
     * @param  mixed $root
     * @param  array $args
     * @param  mixed $context
     * @return Model
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function resolve($root, array $args, $context): Model
    {
        $model = $this->findOrFail($root, $args, $context);
        $this->authorize('update', $model);

        $input = $args['input'];
        $model->updateWithInput($input);

        return $model;
    }
}
