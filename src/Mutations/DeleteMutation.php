<?php

namespace Bakery\Mutations;

use GraphQL\Type\Definition\Type;
use Illuminate\Database\Eloquent\Model;

class DeleteMutation extends EntityMutation
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

        return 'delete'.$this->schema->typename();
    }

    /**
     * Get the return type of the mutation.
     *
     * @return Type
     */
    public function type(): Type
    {
        return Type::boolean();
    }

    /**
     * Get the arguments of the mutation.
     *
     * @return array
     */
    public function args(): array
    {
        return $this->schema->getLookupFields();
    }

    /**
     * Resolve the mutation.
     *
     * @param  mixed $root
     * @param  array $args
     * @param  mixed $viewer
     * @return Model
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Exception
     */
    public function resolve($root, array $args, $viewer): Model
    {
        $model = $this->findOrFail($root, $args, $viewer);
        $this->authorize('delete', $model);

        $model->delete();

        return $model;
    }
}
