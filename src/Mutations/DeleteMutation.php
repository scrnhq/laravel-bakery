<?php

namespace Bakery\Mutations;

use Bakery\Fields\Field;
use Bakery\Support\Arguments;
use Bakery\Types\Definitions\RootType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Access\AuthorizationException;

class DeleteMutation extends EloquentMutation
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

        return 'delete'.$this->modelSchema->getTypename();
    }

    /**
     * Get the return type of the mutation.
     *
     * @return RootType
     */
    public function type(): RootType
    {
        return $this->registry->boolean();
    }

    /**
     * Get the arguments of the mutation.
     *
     * @return array
     */
    public function args(): array
    {
        return $this->modelSchema->getLookupFields()->map(function (Field $field) {
            return $field->getType();
        })->toArray();
    }

    /**
     * Resolve the mutation.
     *
     * @param Arguments $args
     * @return Model
     * @throws AuthorizationException
     */
    public function resolve(Arguments $args): Model
    {
        /** @var Model $model */
        $model = $this->findOrFail($args);

        $modelSchema = $this->registry->getSchemaForModel($model);
        $modelSchema->authorizeToDelete();

        $model->delete();

        return $model;
    }
}
