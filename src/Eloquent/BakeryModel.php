<?php

namespace Bakery\Eloquent;

use Bakery;
use Bakery\Utils\Utils;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Bakery\Events\BakeryModelSaved;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Auth\Access\Gate;
use Bakery\Eloquent\Concerns\InteractsWithRelations;

class BakeryModel
{
    use InteractsWithRelations;

    protected $model;

    protected $lookupFields = [];

    private $instance;

    private $gate;

    private $policy;

    /**
     * A static property that tells Bakery
     * if the model is readOnly or not.
     *
     * In the case of a readOnly model, no mutations will be registered.
     *
     * @var bool
     */
    public static $readOnly = false;

    /**
     * The queue of closures that should be called
     * after the entity is persisted.
     *
     * @var array
     */
    private $transactionQueue = [];

    public function __construct(Model $model = null)
    {
        if (isset($model)) {
            $this->model = get_class($model);
            $this->instance = $model;
        } else {
            if ($this->instance) {
                return $this->instance;
            }

            Utils::invariant(
                $this->model,
                'Model not set on '.class_basename($this)
            );

            $this->instance = resolve($this->model);
        }

        $this->gate = app(Gate::class);
        $this->policy = $this->gate->getPolicyFor($this->instance);
    }

    private function normalizeFields(Collection $fields): Collection
    {
        return $fields->map(function ($field) {
            return Utils::toFieldArray($field);
        });
    }

    public function getModel()
    {
        return $this->instance;
    }

    public function getModelClass()
    {
        return $this->model;
    }

    /**
     * The fields that can be used to look up this model.
     *
     * @return array
     */
    public function fields(): array
    {
        return [];
    }

    private function getKeyField(): array
    {
        return [
            $this->getModel()->getKeyName() => ['type' => Type::nonNull(Type::ID())],
        ];
    }

    final public function getFields(): Collection
    {
        return collect($this->getKeyField())->merge(
            $this->normalizeFields(collect($this->fields()))
        );
    }

    final public function getFillableFields(): Collection
    {
        return $this->normalizeFields(collect($this->fields()));
    }

    /**
     * The fields that can be used to look up this model.
     *
     * @return array
     */
    final public function getLookupFields(): array
    {
        $fields = collect($this->getFields())
            ->filter(function ($field, $key) {
                return in_array($key, $this->lookupFields);
            });

        $relations = collect($this->getRelations())->map(function ($type) {
            if (is_array($type)) {
                $type = $type['type'];
            }

            $lookupTypeName = Type::getNamedType($type)->name.'LookupType';

            return Bakery::type($lookupTypeName);
        });

        return Utils::nullifyFields(
            $fields->merge($relations)->merge($this->getKeyField())
        )->toArray();
    }

    /**
     * The relations of the model.
     *
     * @return array
     */
    public function relations(): array
    {
        return [];
    }

    final public function getRelations(): Collection
    {
        return $this->normalizeFields(collect($this->relations()));
    }

    private function bootQuery(): Builder
    {
        return $this->getModel()->query();
    }

    final public function query(): Builder
    {
        return $this->scopeQuery($this->bootQuery());
    }

    public function scopeQuery(Builder $query): Builder
    {
        return $query;
    }

    /**
     * Get the connections of the resource.
     *
     * @return array
     */
    public function connections(): array
    {
        return collect($this->relations())->map(function ($value, $key) {
            $relationType = $this->resolveRelation($key);
            if ($this->isPluralRelation($relationType)) {
                return str_singular($key).'Ids';
            }

            return $key.'Id';
        })->all();
    }

    /**
     * Create a new instance with GraphQL input.
     *
     * @param array $input
     * @return self
     */
    public function createWithGraphQLInput(array $input)
    {
        return DB::transaction(function () use ($input) {
            // TODO: How do we make sure we have a fresh model here?
            $this->fill($input);
            $this->save();

            return $this->instance->fresh();
        });
    }

    /**
     * Update the model with GraphQL input.
     *
     * @param array $input
     * @return self
     */
    public function updateWithGraphQLInput(array $input)
    {
        return DB::transaction(function () use ($input) {
            $this->fill($input);
            $this->save();

            return $this;
        });
    }

    public function fill(array $input)
    {
        $scalars = $this->getFillableScalars($input);
        $relations = $this->getFillableRelations($input);
        $connections = $this->getFillableConnections($input);

        $this->fillScalars($scalars);
        $this->fillRelations($relations);
        $this->fillConnections($connections);

        return $this;
    }

    public function save()
    {
        $this->instance->save();
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
            return in_array($key, array_keys($this->fields()));
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
            return in_array($key, array_keys($this->relations()));
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
            return in_array($key, $this->connections());
        })->toArray();
    }

    /**
     * Fill the scalars in the model.
     *
     * @param array $scalars
     */
    protected function fillScalars(array $scalars)
    {
        foreach ($scalars as $key => $value) {
            try {
                $this->fillScalar($key, $value);
            } catch (Exception $previous) {
                throw new UserError('Could not set '.$key, [
                    $key => $previous->getMessage(),
                ]);
            }
        }
    }

    /**
     * Fill a scalar.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    protected function fillScalar(string $key, $value)
    {
        $policyMethod = 'set'.studly_case($key);

        if (method_exists($this->policy, $policyMethod)) {
            $this->gate->authorize($policyMethod, [$this, $value]);
        }

        return $this->instance->setAttribute($key, $value);
    }

    /**
     * Persist the DB transactions that are queued.
     *
     * @return void
     */
    public function persistQueuedDatabaseTransactions()
    {
        foreach ($this->transactionQueue as $key => $closure) {
            $closure($this);
            unset($this->transactionQueue[$key]);
        }
    }

    public function __call($name, $arguments)
    {
        $this->instance->{$name}(...$arguments);
    }
}
