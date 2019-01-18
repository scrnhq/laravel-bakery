<?php

namespace Bakery\Mutations;

use Bakery\Fields\Field;
use Bakery\Types\Definitions\Type;
use Illuminate\Database\Eloquent\Model;
use GraphQL\Type\Definition\ResolveInfo;

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

        return 'delete'.$this->modelSchema->typename();
    }

    /**
     * Get the return type of the mutation.
     *
     * @return Type
     */
    public function type(): Type
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
     * @param  mixed $root
     * @param  array $args
     * @param  mixed $context
     * @param  \GraphQL\Type\Definition\ResolveInfo $info
     * @return Model
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Exception
     */
    public function resolve($root, array $args, $context, ResolveInfo $info): Model
    {
        $model = $this->findOrFail($root, $args, $context, $info);
        $modelSchema = $this->registry->getSchemaForModel($model);
        $modelSchema->authorizeToDelete();

        $model->delete();

        return $model;
    }
}
