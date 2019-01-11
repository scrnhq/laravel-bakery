<?php

namespace Bakery\Mutations;

use Bakery\Fields\Field;
use Illuminate\Database\Eloquent\Model;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Database\Eloquent\Relations;
use Bakery\Types\Concerns\InteractsWithPivot;

class DetachPivotMutation extends EloquentMutation
{
    use InteractsWithPivot;

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

        $relation = studly_case($this->pivotRelationName);

        return 'detach'.$relation.'On'.$this->modelSchema->typename();
    }

    /**
     * Get the pivot relation for a model.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return mixed
     */
    protected function getRelation(Model $model): Relations\BelongsToMany
    {
        return $model->{$this->pivotRelationName}();
    }

    /**
     * Get the arguments of the mutation.
     *
     * @return array
     */
    public function args(): array
    {
        return $this->modelSchema->getLookupFields()
            ->map(function (Field $field) {
                return $field->getType();
            })
            ->merge(['input' => $this->registry->ID()->list()])
            ->toArray();
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
        $model = $this->findOrFail($root, $args, $context, $info);
        $modelSchema = $this->registry->getSchemaForModel($model);
        $relation = $this->getRelation($model);

        $models = $relation->findMany($args['input'])->each(function (Model $model) use ($relation, $modelSchema) {
            $modelSchema->authorizeToDetach($model);
        });

        $relation->detach($models);

        return $model;
    }
}
