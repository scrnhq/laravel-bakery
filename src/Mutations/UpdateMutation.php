<?php

namespace Bakery\Mutations;

use Bakery\Utils\Utils;
use Illuminate\Database\Eloquent\Model;

class UpdateMutation extends EntityMutation
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
        $model = $this->findOrFail($root, $args, $viewer);
        $this->authorize('update', $model);

        $input = $args['input'];
        $model->updateWithInput($input);

        return $model;
    }
}
