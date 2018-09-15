<?php

namespace Bakery\Mutations;

use Bakery\Support\Facades\Bakery;
use Illuminate\Database\Eloquent\Model;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Database\Eloquent\Relations;

class AttachPivotMutation extends EntityMutation
{
    /**
     * The pivot relationship.
     *
     * @var Relations\BelongsToMany
     */
    protected $pivotRelation;

    /**
     * Get the name of the mutation.
     *
     * @return string
     */
    public function name(): string
    {
        if (property_exists($this, 'name')) {
            return $this->name;
        }

        $relation = studly_case($this->pivotRelation->getRelationName());

        return 'attach'.$relation.'On'.$this->schema->typename();
    }

    /**
     * Set the pivot relation.
     *
     * @param Relations\BelongsToMany $relation
     * @return \Bakery\Mutations\AttachPivotMutation
     */
    public function setPivotRelation(Relations\BelongsToMany $relation)
    {
        $this->pivotRelation = $relation;

        return $this;
    }

    /**
     * Get the schema of the pivot model.
     *
     * @return mixed
     */
    protected function getPivotSchema()
    {
        $pivot = $this->pivotRelation->getPivotClass();

        return Bakery::hasSchemaForModel($pivot)
            ? Bakery::getSchemaForModel($pivot)
            : null;
    }

    /**
     * Get the pivot relation for a model.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return mixed
     */
    protected function getRelation(Model $model): Relations\BelongsToMany
    {
        return $model->{$this->pivotRelation->getRelationName()}();
    }

    /**
     * Get the arguments of the mutation.
     *
     * @return array
     */
    public function args(): array
    {
        $args = collect($this->schema->getLookupFields());
        $relation = $this->pivotRelation->getRelationName();

        if ($this->getPivotSchema()) {
            $typename = studly_case($relation).'PivotInput';
            $args->put('input', Bakery::type($typename)->list());
        } else {
            $args->put('input', Bakery::ID()->list());
        }

        return $args->toArray();
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
        $model = $this->findOrFail($root, $args, $context, $info);
        $relation = $this->getRelation($model);
        $modelSchema = Bakery::getSchemaForModel($model);

        $permission = 'set'.studly_case($relation->getRelationName());
        $modelSchema->authorize($permission, $model);

        $relatedKey = $relation->getRelated()->getKeyName();
        $accessor = $relation->getPivotAccessor();

        $data = collect($args['input'])->mapWithKeys(function ($data, $key) use ($accessor, $relatedKey) {
            if (! is_array($data)) {
                return [$key => $data];
            }

            return [$data[$relatedKey] => $data[$accessor]];
        });

        $relation->attach($data);

        return $model;
    }
}
