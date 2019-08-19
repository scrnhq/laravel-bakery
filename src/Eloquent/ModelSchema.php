<?php

namespace Bakery\Eloquent;

use Bakery\Utils\Utils;
use Bakery\Fields\Field;
use Bakery\Fields\EloquentField;
use Bakery\Support\TypeRegistry;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Bakery\Eloquent\Concerns\Authorizable;
use Bakery\Eloquent\Concerns\MutatesModel;

abstract class ModelSchema
{
    use Authorizable;
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
     * Indicates if the model can be indexed.
     *
     * @var bool
     */
    protected $indexable = true;

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
                'Defined model on '.class_basename($this).' is not a subclass of '.Model::class
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
     * Get the fields of the model.
     *
     * @return array
     */
    public function fields(): array
    {
        return [];
    }

    /**
     * Get the relation fields of the model.
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
     * Returns if the schema is searchable.
     *
     * @return bool
     */
    public function isSearchable()
    {
        return $this->getSearchableFields()->merge($this->getSearchableRelationFields())->isNotEmpty();
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

        if (! $key) {
            return [];
        }

        $field = $this->registry->field($this->registry->ID())
            ->setName($key)
            ->accessor($key)
            ->fillable(false)
            ->unique();

        return [$key => $field];
    }

    /**
     * Get all the readable fields.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getFields(): Collection
    {
        return collect($this->getKeyField())->merge($this->fields())->map(function (Field $field, string $key) {
            if (! $field->getAccessor()) {
                $field->accessor($key);
            }

            return $field;
        });
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
     * The fields that can be used in fulltext search.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getSearchableFields(): Collection
    {
        return $this->getFields()->filter(function (Field $field) {
            return $field->isSearchable();
        });
    }

    /**
     * The relation fields that can be used in fulltext search.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getSearchableRelationFields(): Collection
    {
        return $this->getRelationFields()->filter(function (Field $field) {
            return $field->isSearchable();
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
            ->map(function (Field $field) {
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
        return collect($this->relations())->map(function (Field $field, string $key) {
            if (! $field->getAccessor()) {
                $field->accessor($key);
            }

            return $field;
        });
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
        return collect($this->relations())->map(function (Field $field, string $key) {
            if (! $field->getAccessor()) {
                $field->accessor($key);
            }

            return $field;
        })->map(function (Field $field) {
            $key = $field->getAccessor();

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
     * Get the instance of the model schema.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function getInstance(): Model
    {
        return $this->instance;
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
