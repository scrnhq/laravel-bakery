<?php

namespace Bakery\Mutations;

use GraphQL\Type\Definition\ResolveInfo;
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
     * @param \GraphQL\Type\Definition\ResolveInfo $info
     * @return Model
     */
    public function resolve($root, array $args, $context, ResolveInfo $info): Model
    {
        $input = $args['input'];

        return DB::transaction(function () use ($input) {
            $this->schema->authorize('create');
            $model = $this->schema->create($input);

            return $model;
        });
    }
}
