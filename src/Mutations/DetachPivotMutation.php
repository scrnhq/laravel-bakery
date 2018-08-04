<?php

namespace Bakery\Mutations;

use Bakery\Utils\Utils;
use Bakery\Support\Facades\Bakery;
use Illuminate\Database\Eloquent\Model;
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
     * @return this
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
     * @param  mixed $viewer
     * @return Model
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function resolve($root, array $args, $viewer): Model
    {
        $model = $this->findOrFail($root, $args, $viewer);
        $relation = $model->{$this->pivotRelation->getRelationName()}();

        $permission = 'set'.studly_case($relation->getRelationName());
        $this->authorize($permission, $model);

        $input = $args['input'];
        $relation->detach($input);

        return $model;
    }
}
