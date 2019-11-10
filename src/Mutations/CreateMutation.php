<?php

namespace Bakery\Mutations;

use Bakery\Support\Arguments;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

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
     * @param Arguments $args
     * @return Model
     */
    public function resolve(Arguments $args): Model
    {
        $input = $args->input->toArray();

        return DB::transaction(function () use ($input) {
            return $this->getModelSchema()->createIfAuthorized($input);
        });
    }
}
