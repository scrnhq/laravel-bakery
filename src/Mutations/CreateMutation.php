<?php

namespace Bakery\Mutations;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class CreateMutation extends EntityMutation
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

        return 'create'.$this->schema->typename();
    }

    /**
     * Resolve the mutation.
     *
     * @param  mixed $root
     * @param  array $args
     * @param  mixed $context
     * @return Model
     */
    public function resolve($root, array $args, $context): Model
    {
        $input = $args['input'];

        return DB::transaction(function () use ($input) {
            $model = $this->model->createWithInput($input);
            $this->authorize('create', [$model]);

            return $model;
        });
    }
}
