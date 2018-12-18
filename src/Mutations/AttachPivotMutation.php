<?php

namespace Bakery\Mutations;

use Bakery\Fields\Field;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use GraphQL\Type\Definition\ResolveInfo;
use Bakery\Types\Concerns\InteractsWithPivot;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AttachPivotMutation extends EloquentMutation
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

        return 'attach'.$relation.'On'.$this->modelSchema->typename();
    }

    /**
     * Get the arguments of the mutation.
     *
     * @return array
     */
    public function args(): array
    {
        $args = $this->modelSchema->getLookupFields()->map(function (Field $field) {
            return $field->getType();
        });

        $relation = $this->relation->getRelationName();

        if ($this->getPivotModelSchema()) {
            $typename = studly_case($relation).'PivotInput';
            $args->put('input', $this->registry->type($typename)->list());
        } else {
            $args->put('input', $this->registry->ID()->list());
        }

        return $args->toArray();
    }

    /**
     * Get the pivot relation.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    protected function getRelation(Model $model): BelongsToMany
    {
        return $model->{$this->pivotRelationName}();
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
     */
    public function resolve($root, array $args, $context, ResolveInfo $info): Model
    {
        $input = $args['input'];
        $model = $this->findOrFail($root, $args, $context, $info);

        return DB::transaction(function () use ($input, $model) {
            $modelSchema = $this->registry->getSchemaForModel($model);

            $relation = $this->getRelation($model);

            $permission = 'set'.studly_case($relation->getRelationName());
            $modelSchema->authorize($permission, $model);
            $modelSchema->connectBelongsToManyRelation($relation, $input, false);
            $modelSchema->save();

            return $modelSchema->getInstance();
        });
    }
}
