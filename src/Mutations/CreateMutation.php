<?php

namespace Bakery\Mutations;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use GraphQL\Type\Definition\ResolveInfo;

class CreateMutation extends EloquentMutation
{
    /**
     * Get the name of the mutation.
     *
     * @return string
     */
    public function name(): string
    {
        if (isset($this->name)) {
            return $this->name;
        }

        return 'create'.$this->modelSchema->typename();
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
            $this->modelSchema->authorize('create');
            $model = $this->modelSchema->create($input);

            return $model;
        });
    }
}
