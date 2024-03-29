<?php

namespace Bakery\Eloquent\Concerns;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

trait MutatesModel
{
    use QueuesTransactions;
    use InteractsWithRelations;
    use InteractsWithAttributes;

    /**
     * @var \Illuminate\Contracts\Auth\Access\Gate
     */
    protected $gate;

    /**
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $instance;

    /**
     * Return the policy of the class.
     *
     * @return mixed
     */
    protected function policy()
    {
        return $this->gate->getPolicyFor($this->instance);
    }

    /**
     * Execute a callable in a database transaction and inform the model about that.
     *
     * @param  callable  $callback
     * @return mixed
     */
    public function transaction(callable $callback)
    {
        if (method_exists($this->getInstance(), 'startTransaction')) {
            $this->getInstance()->startTransaction();
        }

        $result = DB::transaction($callback);

        if (method_exists($this->getInstance(), 'endTransaction')) {
            $this->getInstance()->endTransaction();
        }

        return $result;
    }

    /**
     * Create a new instance with GraphQL input.
     *
     * @param  array  $input
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function create(array $input = []): Model
    {
        return $this->transaction(function () use ($input) {
            $this->make($input);
            $this->save();

            return $this->instance;
        });
    }

    /**
     * Create a model after checking if the user is authorised to do so.
     *
     * @param  array  $input
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function createIfAuthorized(array $input = []): Model
    {
        return $this->transaction(function () use ($input) {
            $this->create($input);
            $this->authorizeToCreate();

            return $this->instance;
        });
    }

    /**
     * @param  array  $input
     * @return \Illuminate\Database\Eloquent\Model
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function make(array $input = []): Model
    {
        $this->instance = $this->instance->newInstance();
        $this->fill($input);

        return $this->instance;
    }

    /**
     * Update the model with GraphQL input.
     *
     * @param  array  $input
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function update(array $input = []): Model
    {
        return $this->transaction(function () use ($input) {
            $this->fill($input);
            $this->save();

            return $this->instance;
        });
    }

    /**
     * Update a model after checking if the user is authorised to do so.
     *
     * @param  array  $input
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function updateIfAuthorized(array $input = []): Model
    {
        $this->authorizeToUpdate();
        $this->update($input);

        return $this->instance;
    }

    /**
     * Fill the underlying model with input.
     *
     * @param  array  $input
     * @return $this
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function fill(array $input = [])
    {
        $scalars = $this->getFillableScalars($input);
        $relations = $this->getFillableRelations($input);
        $connections = $this->getFillableConnections($input);

        $this->fillScalars($scalars);
        $this->fillRelations($relations);
        $this->fillConnections($connections);

        $this->checkScalars($scalars);

        return $this;
    }

    /**
     * Save the underlying model.
     *
     * @return $this
     */
    public function save(): self
    {
        $this->instance->save();

        $this->persistQueuedDatabaseTransactions();

        return $this;
    }

    /**
     * Get the attributes that are mass assignable by cross
     * referencing the attributes with the GraphQL fields.
     *
     * @param  array  $attributes
     * @return array
     */
    protected function getFillableScalars(array $attributes): array
    {
        $fields = $this->getFillableFields();

        return collect($attributes)->intersectByKeys($fields)->toArray();
    }

    /**
     * Get the relations that are assignable by cross referencing
     * the attributes with the GraphQL relations.
     *
     * @param  array  $attributes
     * @return array
     */
    protected function getFillableRelations(array $attributes): array
    {
        $relations = $this->getRelationFields();

        return collect($attributes)->intersectByKeys($relations)->toArray();
    }

    /**
     * Get the relations that are assignable by cross referencing
     * the attributes with the GraphQL connections.
     *
     * @param  array  $attributes
     * @return array
     */
    protected function getFillableConnections(array $attributes): array
    {
        $connections = $this->getConnections();

        return collect($attributes)->intersectByKeys($connections->flip())->toArray();
    }
}
