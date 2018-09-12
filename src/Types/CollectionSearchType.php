<?php

namespace Bakery\Types;

use Bakery\Concerns\ModelAware;
use Bakery\Support\Facades\Bakery;
use Bakery\Types\Definitions\Type;
use Bakery\Types\Definitions\BaseType;
use Bakery\Types\Definitions\InputType;
use Bakery\Types\Definitions\EloquentType;

class CollectionSearchType extends InputType
{
    use ModelAware;

    /**
     * Get the name of the Collection Search Type.
     *
     * @return string
     */
    public function name(): string
    {
        return $this->schema->typename().'Search';
    }

    /**
     * Return the fields for the collection filter type.
     *
     * @return array
     */
    public function fields(): array
    {
        $fields = collect($this->schema->getFields());

        $fields->each(function (Type $field, $name) use (&$fields) {
            $fields->put($name, Bakery::boolean()->nullable());
        });

         foreach ($this->schema->getFields() as $name => $field) {
             if ($field instanceof BaseType) {
                 $fields[$name] = Bakery::boolean()->nullable();
             }
         }

         foreach ($this->schema->getRelationFields() as $relation => $field) {
             if ($field instanceof EloquentType) {
                 $fields[$relation] = Bakery::type($field->name().'Search')->nullable();
             }
         }

        return $fields->toArray();
    }
}
