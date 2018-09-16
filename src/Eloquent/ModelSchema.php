<?php

namespace Bakery\Eloquent;

use Bakery\Bakery;
use Bakery\Utils\Utils;
use Bakery\Types\Definitions\Type;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Bakery\Eloquent\Concerns\MutatesModel;
use Bakery\Types\Definitions\EloquentType;
use Illuminate\Contracts\Auth\Access\Gate;
use Bakery\Eloquent\Concerns\InteractsWithQueries;
use Illuminate\Auth\Access\AuthorizationException;

abstract class ModelSchema
{
    use MutatesModel;
    use InteractsWithQueries;

    /**
     * @var \Bakery\Bakery
     */
    protected $bakery;

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
     * ModelSchema constructor.
     *
     * @param Model $instance
     */
    public function __construct(Model $instance = null)
    {
        $this->gate = resolve(Gate::class);
        $this->bakery = resolve(Bakery::class);

        if (isset($instance)) {
            $this->instance = $instance;
        } else {
            $model = $this->model();

            Utils::invariant(isset($model), 'No model defined on '.class_basename($this));

            Utils::invariant(
                is_subclass_of($model, Model::class),
                'Defined model on '.class_basename($this).' is not an instance of '.Model::class
            );

            $this->instance = resolve($model);
        }

        return $this;
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
        return $this->mutable;
    }

    /**
     * Return the typename of the model schema.
     *
     * @return string
     */
    public function typename(): string
    {
        return Utils::typename($this->getModel());
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
        $allowed = $this->gate->allows($policy, [$this->instance, $attributes]);

        if (! $allowed) {
            throw new AuthorizationException(
                'Not allowed to perform '.$policy.' on '.$this->getModelClass()
            );
        }
    }

    /**
     * Get the key (ID) field.
     *
     * @return array
     */
    protected function getKeyField(): array
    {
        return [$this->instance->getKeyName() => $this->bakery->ID()->fillable(false)];
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
     * Get all the readable fields.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getFields(): Collection
    {
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
        return $this->getFields()->filter(function (Type $field, $key) {
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
            ->filter(function (Type $field) {
                return $field->isUnique();
            });

        $relations = collect($this->getRelationFields())
            ->filter(function (Type $field) {
                return $field instanceof EloquentType;
            })
            ->map(function (EloquentType $field) {
                $lookupTypeName = $field->name().'LookupType';

                return $this->bakery->type($lookupTypeName)->nullable();
            });

        return collect($this->getKeyField())
            ->merge($fields)
            ->merge($relations)
            ->map(function (Type $field) {
                return $field->nullable();
            });
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
     * Get the relational fields.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getRelationFields(): Collection
    {
        return collect($this->relations());
    }

    /**
     * Get the fillable relational fields.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getFillableRelationFields(): Collection
    {
        return $this->getRelationFields()->filter(function (Type $field) {
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
        return collect($this->getRelationFields())->map(function (Type $field, $key) {
            return $field->isList() ? str_singular($key).'Ids' : $key.'Id';
        });
    }
}
