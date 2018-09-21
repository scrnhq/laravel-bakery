<?php

namespace Bakery\Eloquent;

use Bakery\Bakery;
use Bakery\Utils\Utils;
use Bakery\TypeRegistry;
use Bakery\Fields\Field;
use Bakery\Fields\EloquentField;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Bakery\Eloquent\Concerns\MutatesModel;
use Illuminate\Contracts\Auth\Access\Gate;
use Bakery\Eloquent\Concerns\InteractsWithQueries;
use Illuminate\Auth\Access\AuthorizationException;

abstract class ModelSchema
{
    use MutatesModel;
    use InteractsWithQueries;

    /**
     * @var \Bakery\Support\Schema
     */
    protected $schema;

    /**
     * @var \Bakery\TypeRegistry
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
     * @param \Bakery\TypeRegistry $registry
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
     * Check if the user is authorised to perform an action on the model.
     *
     * @param string $policy
     * @param array $attributes
     * @throws AuthorizationException
     */
    public function authorize(string $policy, $attributes = null)
    {
        $allowed = $this->getGate()->allows($policy, [$this->instance, $attributes]);

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
        $key = $this->instance->getKeyName();

        return [$key => $this->registry->field($this->registry->ID())->fillable(false)->unique()];
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

    public function __sleep()
    {
        return ['registry'];
    }

    public function __wakeup()
    {
        //
    }

    /**
     * @return \Bakery\TypeRegistry
     */
    public function getRegistry(): TypeRegistry
    {
        return $this->registry;
    }

    /**
     * @param \Bakery\TypeRegistry $registry
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
}
