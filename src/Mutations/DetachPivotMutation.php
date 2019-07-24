<?php

namespace Bakery\Mutations;

use Bakery\Fields\Field;
use Illuminate\Support\Str;
use Bakery\Support\Arguments;
use Illuminate\Database\Eloquent\Model;
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
        $relation = $this->relation->getRelationName();

        if ($this->getPivotModelSchema()) {
            $typename = Str::studly($relation).'PivotInput';
            $type = $this->registry->type($typename)->list();
        } else {
            $type = $this->registry->ID()->list();
        }

        return $this->modelSchema->getLookupFields()->map(function (Field $field) {
            return $field->getType();
        })->merge(['input' => $type])->toArray();
    }

    /**
     * Resolve the mutation.
     *
     * @param Arguments $args
     * @return Model
     */
    public function resolve(Arguments $args): Model
    {
        $model = $this->findOrFail($args);
        $modelSchema = $this->registry->getSchemaForModel($model);
        $relation = $this->getRelation($model);

        $input = collect($args['input']);
        $pivotAccessor = $relation->getPivotAccessor();
        $pivotSchema = $this->getPivotModelSchema();

        $input->map(function ($input) use ($pivotSchema, $relation, $pivotAccessor, $model) {
            $key = $input[$model->getKeyName()] ?? $input;
            $pivotWhere = $input[$pivotAccessor] ?? [];

            $query = $this->getRelation($model);

            foreach ($pivotWhere as $column => $value) {
                // Transform modelId to model_id.
                if ($pivotSchema->getRelationFields()->keys()->contains(Str::before($column, 'Id'))) {
                    $column = Str::snake($column);
                }

                $query->wherePivot($column, $value);
            }

            $query->wherePivot($relation->getRelatedPivotKeyName(), $key);

            return $query;
        })->map(function (Relations\BelongsToMany $query) use ($relation, $modelSchema) {
            $query->each(function (Model $related) use ($modelSchema) {
                $modelSchema->authorizeToDetach($related);
            });

            return $query;
        })->each(function (Relations\BelongsToMany $query) use ($relation) {
            $query->detach();
        });

        // Refresh the model to accommodate for any side effects
        // that the pivot relation may have caused.
        return $model->refresh();
    }
}
