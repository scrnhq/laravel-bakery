<?php

namespace Bakery\Eloquent;

use Bakery\Utils\Utils;
use Bakery\Support\Facades\Bakery;
use Bakery\Types\Definitions\Type;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Bakery\Types\Definitions\EloquentType;

trait Introspectable
{
    use Concerns\InteractsWithQueries;

    /**
     * A reference to the underlying Eloquent instance.
     *
     * @var mixed
     */
    protected $instance = null;

    /**
     * Return the typename of the model.
     *
     * @return string
     */
    public function typename(): string
    {
        return Utils::typename($this->getModel());
    }

    /**
     * Get the underlying model.
     *
     * If $this is already an Eloquent model, we just return this.
     * Otherwise we boot up an instance of that model and return it.
     *
     * @return mixed
     */
    public function getModel()
    {
        if ($this instanceof Model) {
            return $this;
        }

        if (isset($this->instance)) {
            return $this->instance;
        }

        Utils::invariant(
            isset(self::$model),
            'No model defined on '.class_basename($this)
        );

        Utils::invariant(
            is_subclass_of(self::$model, Model::class),
            'Defined model on '.class_basename($this).' is not an instance of '.Model::class
        );

        return $this->instance = resolve(self::$model);
    }

    /**
     * Get the key (ID) field.
     *
     * @return array
     */
    private function getKeyField(): array
    {
        return [$this->getKeyName() => Bakery::ID()];
    }

    /**
     * Define the fields of the model.
     * This method can be overridden.
     */
    public function fields(): array
    {
        return [];
    }

    /**
     * Get all the readable fields.
     *
     * @return Collection
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
     * @return Collection
     */
    public function getFillableFields(): Collection
    {
        return $this->getFields()->filter(function (Type $field, $key) {
            return collect($this->getFillable())->contains($key);
        });
    }

    /**
     * The fields that can be used to look up this model.
     *
     * @return Collection
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

                return Bakery::type($lookupTypeName)->nullable();
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
    public function getRelationFields(): Collection
    {
        return method_exists($this, 'relations')
            ? collect($this->relations()) : collect();
    }

    /**
     * Get the fillable relational fields.
     *
     * @return Collection
     */
    public function getFillableRelationFields(): Collection
    {
        return $this->getRelationFields()->filter(function (Type $field, $key) {
            return collect($this->getFillable())->contains($key);
        });
    }

    /**
     * Get the Eloquent relations of the model.
     * This will only return relations that are defined in the model schema.
     *
     * @return Collection
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
     * @return array
     */
    public function getConnections(): array
    {
        return collect($this->getRelationFields())->map(function ($field, $key) {
            if ($field->isList()) {
                return str_singular($key).'Ids';
            }

            return $key.'Id';
        })->all();
    }

    /**
     * Pass through any calls to the underlying model if $this
     * is not an instance of Eloquent.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if ($this instanceof Model) {
            return parent::__call($method, $parameters);
        }

        return $this->getModel()->{$method}($parameters);
    }
}
