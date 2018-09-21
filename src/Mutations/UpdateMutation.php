<?php

namespace Bakery\Mutations;

use Bakery\Fields\Field;
use Illuminate\Database\Eloquent\Model;
use GraphQL\Type\Definition\ResolveInfo;

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

        return 'update'.$this->modelSchema->typename();
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
     * @param  mixed $root
     * @param  array $args
     * @param  mixed $context
     * @param \GraphQL\Type\Definition\ResolveInfo $info
     * @return Model
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function resolve($root, array $args, $context, ResolveInfo $info): Model
    {
        $model = $this->findOrFail($root, $args, $context, $info);
        $modelSchema = $this->registry->getSchemaForModel($model);

        $modelSchema->authorize('update');
        $modelSchema->update($args['input']);

        return $model;
    }
}
