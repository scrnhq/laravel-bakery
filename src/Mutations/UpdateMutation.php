<?php

namespace Bakery\Mutations;

use Bakery\Fields\Field;
use Bakery\Support\Arguments;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class UpdateMutation extends EloquentMutation
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

        return 'update'.$this->modelSchema->getTypename();
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
            $this->modelSchema->getLookupFields()->map(function (Field $field) {
                return $field->getType();
            })->toArray()
        );
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
        $model = $this->findOrFail($args);

        return DB::transaction(function () use ($input, $model) {
            $modelSchema = $this->registry->getSchemaForModel($model);

            return $modelSchema->updateIfAuthorized($input);
        });
    }
}
