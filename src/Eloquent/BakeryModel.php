<?php

namespace Bakery\Eloquent;

use Bakery;
use Bakery\Utils\Utils;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Bakery\Events\BakeryModelSaved;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Access\Gate;
use Bakery\Eloquent\Concerns\QueuesTransactions;
use Bakery\Eloquent\Concerns\InteractsWithRelations;
use Bakery\Eloquent\Concerns\InteractsWithAttributes;

class BakeryModel
{
    use QueuesTransactions;
    use InteractsWithRelations;
    use InteractsWithAttributes;

    /**
     * The class of the Eloquent model.
     *
     * @var string
     */
    protected $model;

    /**
     * The underlying instance of the Eloquent model.
     *
     * @var Model
     */
    private $instance;

    /**
     * Reference to the Laravel gate.
     *
     * @var Gate
     */
    private $gate;

    /**
     * Reference to the Policy of the model.
     *
     * @var Policy
     */
    private $policy;

    /**
     * The fields that can be used to lookup the model.
     *
     * @var array
     */
    protected $lookupFields = [];

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
     * Construct a new Bakery Model.
     *
     * You can either pass in the model or define it as a property when
     * extending the class.
     *
     * @param Model $model
     */
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

    /**
     * Get the model instance.
     *
     * @return Model
     */
    public function getModel(): Model
    {
        return $this->instance;
    }

    /**
     * Get the class of the model.
     *
     * @return string
     */
    public function getModelClass(): string
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

    /**
     * Get the key (ID) field.
     *
     * @return array
     */
    private function getKeyField(): array
    {
        return [
            $this->getModel()->getKeyName() => ['type' => Type::nonNull(Type::ID())],
        ];
    }

    /**
     * Get all the readable fields.
     *
     * @return Collection
     */
    final public function getFields(): Collection
    {
        return collect($this->getKeyField())->merge(
            Utils::normalizeFields(collect($this->fields()))
        );
    }

    /**
     * Get the fields that can be filled.
     *
     * This excludes the ID field.
     *
     * @return Collection
     */
    final public function getFillableFields(): Collection
    {
        return Utils::normalizeFields(collect($this->fields()));
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

    /**
     * Get the relational fields.
     *
     * @return Collection
     */
    final public function getRelations(): Collection
    {
        return Utils::normalizeFields(collect($this->relations()));
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
    public function create(array $input)
    {
        return DB::transaction(function () use ($input) {
            $instance = Bakery::create($this->model);
            $instance->fill($input);
            $instance->save();

            return $instance;
        });
    }

    /**
     * Update the model with GraphQL input.
     *
     * @param array $input
     * @return self
     */
    public function update(array $input)
    {
        return DB::transaction(function () use ($input) {
            $this->fill($input);
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

    /**
     * Save the underlying method.
     *
     * @return self
     */
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
     * Pass through method calls to the underlying model.
     *
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        return $this->instance->{$name}(...$arguments);
    }
}
