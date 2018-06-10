<?php

namespace Bakery\Eloquent;

use Bakery\Utils\Utils;
use GraphQL\Type\Definition\Type;
use Bakery\Support\Facades\Bakery;
use Illuminate\Support\Collection;
use GraphQL\Type\Definition\UnionType;
use GraphQL\Type\Definition\ListOfType;
use Illuminate\Database\Eloquent\Model;

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
     * Otherwise we boot up an intance of that model and return it.
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
        return [
            $this->getKeyName() => ['type' => Type::nonNull(Type::ID())],
        ];
    }

    /**
     * Get all the readable fields.
     *
     * @return Collection
     */
    public function getFields(): Collection
    {
        $fields = method_exists($this, 'fields') ? $this->fields() : [];

        return collect($this->getKeyField())->merge(
            Utils::normalizeFields($fields)
        );
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
        return Utils::normalizeFields($this->fields() ?? [])->filter(function ($field, $key) {
            return collect($this->getFillable())->contains($key);
        });
    }

    /**
     * The fields that can be used to look up this model.
     *
     * @return array
     */
    public function getLookupFields(): array
    {
        $fields = collect($this->getFields())
            ->filter(function ($field, $key) {
                return in_array($key, $this->lookupFields ?? []);
            });

        $relations = collect($this->getRelations())
            ->filter(function ($field) {
                $field = Type::getNamedType($field['type']);

                return ! $field instanceof UnionType;
            })
            ->map(function ($field) {
                $field = Type::getNamedType($field['type']);
                $lookupTypeName = $field->name.'LookupType';

                try {
                    Bakery::type($lookupTypeName);
                } catch (\Exception $e) {
                    dd($field);
                }

                return Bakery::type($lookupTypeName);
            });

        return Utils::nullifyFields(
            $fields->merge($relations)->merge($this->getKeyField())
        )->toArray();
    }

    /**
     * Get the relational fields.
     *
     * @return Collection
     */
    public function getRelations(): Collection
    {
        $relations = method_exists($this, 'relations') ? $this->relations() : [];

        return Utils::normalizeFields($relations);
    }

    /**
     * Get the connections of the resource.
     *
     * @return array
     */
    public function getConnections(): array
    {
        return collect($this->getRelations())->map(function ($field, $key) {
            $field = Utils::nullifyField($field);

            if ($field['type'] instanceof ListOfType) {
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
