<?php

namespace Bakery\Eloquent;

use Bakery\Eloquent\Concerns;
use Illuminate\Support\Facades\DB;
use Bakery\Support\Facades\Bakery;
use Bakery\Events\BakeryModelSaved;
use Illuminate\Contracts\Auth\Access\Gate;

trait BakeryMutable
{
    use Concerns\QueuesTransactions;
    use Concerns\InteractsWithRelations;
    use Concerns\InteractsWithAttributes;

    protected $schema;

    protected $gate;

    /**
     * Return an instance of the Gate class.
     *
     * @return Gate
     */
    protected function gate(): Gate
    {
        if (!isset($this->gate)) {
            $this->gate = app(Gate::class);
        }

        return $this->gate;
    }

    /**
     * Get the Bakery model schema that belongs to this model.
     *
     * @return mixed
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
     * @return self
     */
    public function createWithInput(array $input)
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
     * @return self
     */
    public function updateWithInput(array $attributes = [], array $options = [])
    {
        return DB::transaction(function () use ($attributes) {
            $this->fillWithInput($attributes);
            $this->save();

            return $this;
        });
    }

    /**
     * Fill the underlying model with input.
     *
     * @param array $input
     * @return self
     */
    public function fillWithInput(array $input)
    {
        $scalars = $this->getFillableScalars($input);
        $relations = $this->getFillableRelations($input);
        $connections = $this->getFillableConnections($input);

        $this->fillScalars($scalars);
        $this->fillRelations($relations);
        $this->fillConnections($connections);

        return $this;
    }

    /**
     * Save the underlying model.
     *
     * @return self
     */
    public function save(array $options = [])
    {
        parent::save($options);
        event(new BakeryModelSaved($this));

        return $this;
    }

    /**
     * Get the attributes that are mass assignable by
     * cross referencing the attributes with the GraphQL fields.
     *
     * @param array $attributes
     * @return array
     */
    protected function getFillableScalars(array $attributes): array
    {
        return collect($attributes)->filter(function ($value, $key) {
            $fields = $this->getSchema()->getFillableFields()->keys();
            return $fields->contains($key);
        })->toArray();
    }

    /**
     * Get the relations that are assignable by
     * cross referencing the attributes with the GraphQL relations.
     *
     * @param array $attributes
     * @return array
     */
    protected function getFillableRelations(array $attributes): array
    {
        return collect($attributes)->filter(function ($value, $key) {
            $relations = $this->getSchema()->getRelations()->keys();
            return $relations->contains($key);
        })->toArray();
    }

    /**
     * Get the relations that are assignable by
     * cross referencing the attributes with the GraphQL relations.
     *
     * @param array $attributes
     * @return array
     */
    protected function getFillableConnections(array $attributes): array
    {
        return collect($attributes)->filter(function ($value, $key) {
            return in_array($key, $this->getSchema()->getConnections());
        })->toArray();
    }
}
