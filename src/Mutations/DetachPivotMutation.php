<?php

namespace Bakery\Mutations;

use Bakery\Support\Facades\Bakery;
use Illuminate\Database\Eloquent\Model;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Database\Eloquent\Relations;

class DetachPivotMutation extends EntityMutation
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

        return 'detach'.$relation.'On'.$this->schema->typename();
    }

    /**
     * Set the pivot relation.
     *
     * @param Relations\BelongsToMany $relation
     * @return \Bakery\Mutations\DetachPivotMutation
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

        return Bakery::hasModelSchema($pivot)
            ? resolve(Bakery::getModelSchema($pivot))
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
        return collect(['input' => Bakery::ID()->list()])
            ->merge($this->schema->getLookupFields())
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
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function resolve($root, array $args, $context, ResolveInfo $info): Model
    {
        $model = $this->findOrFail($root, $args, $context, $info);
        $modelSchema = Bakery::getSchemaForModel($model);
        $relation = $this->getRelation($model);

        $permission = 'set'.studly_case($relation->getRelationName());
        $modelSchema->authorize($permission);

        $relation->detach($args['input']);

        return $model;
    }
}
