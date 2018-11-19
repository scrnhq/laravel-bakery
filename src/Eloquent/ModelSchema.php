<?php

namespace Bakery\Eloquent;

use Bakery\Utils\Utils;
use Bakery\Fields\Field;
use Bakery\Fields\EloquentField;
use Bakery\Support\TypeRegistry;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Bakery\Eloquent\Concerns\MutatesModel;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Auth\Access\AuthorizationException;

abstract class ModelSchema
{
    use MutatesModel;

    /**
     * @var \Bakery\Support\Schema
     */
    protected $schema;

    /**
     * @var \Bakery\Support\TypeRegistry
     */
    protected $registry;

    /**
     * @var \Illuminate\Contracts\Auth\Access\Gate
     */
    protected $gate;

    /**
     * @var string
     */
    protected $model;

    /**
     * @var Model
     */
    protected $instance;

    /**
     * Indicates if the model can be mutated.
     * Setting this to false will make sure no mutations are generated for that model.
     *
     * @var bool
     */
    protected $mutable = true;

    /**
     * Indicates if the model can be 
     * 
     * @var bool 
     */
    protected $indexable = true;

    /**
     * The bound fields.
     *
     * @var Collection
     */
    private $fields;

    /**
     * The bound relations.
     *
     * @var Collection
     */
    private $relations;

    /**
     * ModelSchema constructor.
     *
     * @param \Bakery\Support\TypeRegistry $registry
     * @param \Illuminate\Database\Eloquent\Model|null $instance
     */
    public function __construct(TypeRegistry $registry, Model $instance = null)
    {
        $this->registry = $registry;

        if ($instance) {
            $this->instance = $instance;
        } else {
            $model = $this->model();

            Utils::invariant(
                is_subclass_of($model, Model::class),
                'Defined model on '.class_basename($this).' is not an instance of '.Model::class
            );

            $this->instance = resolve($model);
        }
    }

    /**
     * Define the eloquent model of the model schema.
     *
     * @return string
     */
    protected function model()
    {
        return $this->model;
    }

    /**
     * Define the fields of the model.
     * This method can be overridden.
     *
     * @return array
     */
    public function fields(): array
    {
        return [];
    }

    /**
     * Define the relation fields of the schema.
     * This method can be overridden.
     *
     * @return array
     */
    public function relations(): array
    {
        return [];
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
     * Get the eloquent model of the model schema.
     *
     * @return Model
     */
    public function getModel(): Model
    {
        return $this->instance;
    }

    /**
     * Returns if the schema is mutable.
     *
     * @return bool
     */
    public function isMutable()
    {
        if ($this->getFillableFields()->merge($this->getFillableRelationFields())->isEmpty()) {
            return false;
        }

        return $this->mutable;
    }

    /**
     * Determine if the model is indexable.
     *
     * @return bool
     */
    public function isIndexable()
    {
        return $this->indexable;
    }

    /**
     * Return the typename of the model schema.
     *
     * @return string
     */
    public function getTypename(): string
    {
        return Utils::typename($this->getModel());
    }

    /**
     * @alias getTypename()
     * @return string
     */
    public function typename(): string
    {
        return $this->getTypename();
    }

    /**
     * Get the key (ID) field.
     *
     * @return array
     */
    protected function getKeyField(): array
    {
        $key = $this->instance->getKeyName();

        return [$key => $this->registry->field($this->registry->ID())->fillable(false)->unique()];
    }

    /**
     * Get all the readable fields.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getFields(): Collection
    {
        if (isset($this->fields)) {
            return $this->fields;
        }

        return collect($this->getKeyField())->merge($this->fields());
    }

    /**
     * Get the fields that can be filled.
     *
     * This excludes the ID field and other fields that are guarded from
     * mass assignment exceptions.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getFillableFields(): Collection
    {
        return $this->getFields()->filter(function (Field $field) {
            return $field->isFillable();
        });
    }

    /**
     * The fields that can be used to look up this model.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getLookupFields(): Collection
    {
        $fields = collect($this->getFields())
            ->filter(function (Field $field) {
                return $field->isUnique();
            });

        $relations = collect($this->getRelationFields())
            ->filter(function ($field) {
                return $field instanceof EloquentField;
            })
            ->map(function (EloquentField $field) {
                $lookupTypeName = $field->getName().'LookupType';

                return $this->registry->field($lookupTypeName);
            });

        return collect()
            ->merge($fields)
            ->merge($relations)
            ->map(function (Field $field, $key) {
                return $field->nullable();
            });
    }

    /**
     * Get the lookup types.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getLookupTypes(): Collection
    {
        return $this->getLookupFields()->map(function (Field $field) {
            return $field->getType();
        });
    }

    /**
     * Get the relational fields.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getRelationFields(): Collection
    {
        if (isset($this->relations)) {
            return $this->relations;
        }

        return collect($this->relations());
    }

    /**
     * Get the fillable relational fields.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getFillableRelationFields(): Collection
    {
        return $this->getRelationFields()->filter(function (Field $field) {
            return $field->isFillable();
        });
    }

    /**
     * Get the Eloquent relations of the model.
     * This will only return relations that are defined in the model schema.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getRelations(): Collection
    {
        $relations = collect($this->relations());

        return $relations->map(function ($field, $key) {
            Utils::invariant(
                method_exists($this->getModel(), $key),
                'Relation "'.$key.'" is not defined on "'.get_class($this->getModel()).'".'
            );

            return $this->getModel()->{$key}();
        });
    }

    /**
     * Get the connections of the resource.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getConnections(): Collection
    {
        return collect($this->getRelationFields())->map(function (Field $field, $key) {
            return $field->isList() ? str_singular($key).'Ids' : $key.'Id';
        });
    }

    /**
     * Check if the user is authorised to perform an action on the model.
     *
     * @param string $policy
     * @param array $attributes
     * @throws AuthorizationException
     */
    public function authorize(string $policy, $attributes = null)
    {
        // If the instance on the model schema does not exist or has not been modified we don't want to use it for
        // authorization checks. This usually happens if we have to check if an instance can be created at all.
        $model = $this->instance->exists || $this->instance->isDirty() ? $this->instance : get_class($this->instance);

        $allowed = $this->getGate()->allows($policy, [$model, $attributes]);

        if (! $allowed) {
            throw new AuthorizationException(
                'Not allowed to perform '.$policy.' on '.$this->getModelClass()
            );
        }
    }

    /**
     * Get the instance of the model schema.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function getInstance(): Model
    {
        return $this->instance;
    }

    /**
     * Set the instance on the model schema.
     *
     * @param \Illuminate\Database\Eloquent\Model $instance
     */
    public function setInstance(Model $instance): void
    {
        $model = $this->getModel();
        Utils::invariant(
            $instance instanceof $model,
            'Can not set instance of '.get_class($instance).' on '.get_class($this).' as it differs from defined model which is: '.$this->getModelClass()
        );

        $this->instance = $instance;
    }

    /**
     * Get the registry of the model schema.
     *
     * @return \Bakery\Support\TypeRegistry
     */
    public function getRegistry(): TypeRegistry
    {
        return $this->registry;
    }

    /**
     * Set the registry of the model schema.
     *
     * @param \Bakery\Support\TypeRegistry $registry
     */
    public function setRegistry(TypeRegistry $registry): void
    {
        $this->registry = $registry;
    }

    /**
     * Get an instance to the Laravel gate.
     *
     * @return \Illuminate\Contracts\Auth\Access\Gate
     */
    public function getGate(): Gate
    {
        if (! $this->gate) {
            $this->gate = resolve(Gate::class);
        }

        return $this->gate;
    }

    /**
     * Boot the query builder on the underlying model.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getQuery(): Builder
    {
        $model = $this->getModel();

        return $this->scopeQuery($model->newQuery());
    }

    /**
     * Scope the query on the model schema. This method can be overridden to always
     * scope the query when executing queries/mutations on this schema.
     *
     * Note that this does not work for relations, in these cases you
     * should consider using Laravel's global scopes.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function scopeQuery(Builder $query): Builder
    {
        return $query;
    }
}
