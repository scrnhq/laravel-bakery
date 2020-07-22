<?php

namespace Bakery\Mutations;

use Bakery\Fields\Field;
use Illuminate\Support\Str;
use Bakery\Support\Arguments;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
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

        $relation = Str::studly($this->pivotRelationName);

        return 'attach'.$relation.'On'.$this->modelSchema->getTypename();
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

        return $this->modelSchema->getLookupFields()
            ->map(function (Field $field) {
                return $field->getType();
            })
            ->merge(['input' => $type])
            ->toArray();
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
     * @param Arguments $args
     * @return Model
     */
    public function resolve(Arguments $args): Model
    {
        $input = $args->input->toArray();
        $model = $this->findOrFail($args);

        return DB::transaction(function () use ($input, $model) {
            $modelSchema = $this->registry->getSchemaForModel($model);

            $relation = $this->getRelation($model);

            $modelSchema->connectBelongsToManyRelation($relation, $input, false);
            $modelSchema->save();

            // Refresh the model to accommodate for any side effects
            // that the pivot relation may have caused.
            return $modelSchema->getInstance()->refresh();
        });
    }
}
