<?php

namespace Bakery\Eloquent;

use Bakery\Support\Facades\Bakery;
use Illuminate\Support\Facades\DB;
use Bakery\Events\BakeryModelSaved;
use Illuminate\Contracts\Auth\Access\Gate;

trait Mutable
{
    use Concerns\QueuesTransactions;
    use Concerns\InteractsWithRelations;
    use Concerns\InteractsWithAttributes;

    protected $schema;

    protected $gate;

    /**
     * Return an instance of the Gate class.
     *
     * @return \Illuminate\Contracts\Auth\Access\Gate
     */
    protected function gate(): Gate
    {
        if (! isset($this->gate)) {
            $this->gate = app(Gate::class);
        }

        return $this->gate;
    }

    /**
     * Return the policy of the class.
     *
     * @return mixed
     */
    protected function policy()
    {
        return $this->gate()->getPolicyFor($this);
    }

    /**
     * Get the Bakery model schema that belongs to this model.
     *
     * @return \Bakery\Contracts\Introspectable
     */
    public function getSchema()
    {
        if ($this->schema) {
            return $this->schema;
        }

        return $this->schema = resolve(Bakery::getModelSchema($this));
    }

    /**
     * Create a new instance with GraphQL input.
     *
     * @param array $input
     * @return $this
     */
    public function createWithInput(array $input = [])
    {
        return DB::transaction(function () use ($input) {
            $model = new static();
            $model->fillWithInput($input);
            $model->save();

            return $model;
        });
    }

    /**
     * Update the model with GraphQL input.
     *
     * @param array $input
     * @return $this
     * @throws \Throwable
     */
    public function updateWithInput(array $input = [])
    {
        return DB::transaction(function () use ($input) {
            $this->fillWithInput($input);
            $this->save();

            return $this;
        });
    }

    /**
     * Fill the underlying model with input.
     *
     * @param array $input
     * @return $this
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function fillWithInput(array $input = [])
    {
        $scalars = $this->getFillableScalars($input);
        $relations = $this->getFillableRelations($input);
        $connections = $this->getFillableConnections($input);

        $this->fillScalars($scalars);
        $this->fillRelations($relations);
        $this->fillConnections($connections);

        $this->checkScalars($scalars);
        $this->checkRelations($relations);
        $this->checkConnections($connections);

        return $this;
    }

    /**
     * Save the underlying model.
     *
     * @param array $options
     * @return $this
     */
    public function save(array $options = [])
    {
        parent::save($options);
        event(new BakeryModelSaved($this));

        return $this;
    }

    /**
     * Get the attributes that are mass assignable by cross
     * referencing the attributes with the GraphQL fields.
     *
     * @param array $attributes
     * @return array
     */
    protected function getFillableScalars(array $attributes): array
    {
        $fields = $this->getSchema()->getFillableFields();

        return collect($attributes)->intersectByKeys($fields)->toArray();
    }

    /**
     * Get the relations that are assignable by cross referencing
     * the attributes with the GraphQL relations.
     *
     * @param array $attributes
     * @return array
     */
    protected function getFillableRelations(array $attributes): array
    {
        $relations = $this->getSchema()->getRelationFields();

        return collect($attributes)->intersectByKeys($relations)->toArray();
    }

    /**
     * Get the relations that are assignable by cross referencing
     * the attributes with the GraphQL connections.
     *
     * @param array $attributes
     * @return array
     */
    protected function getFillableConnections(array $attributes): array
    {
        $connections = $this->getSchema()->getConnections();

        return collect($attributes)->intersectByKeys($connections->flip())->toArray();
    }
}
