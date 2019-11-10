<?php

namespace Bakery\Support;

use Bakery\Types\Definitions\ObjectType;
use Illuminate\Support\Collection;

class RootQuery extends ObjectType
{
    /**
     * RootQuery constructor.
     *
     * @param \Bakery\Support\TypeRegistry $registry
     * @param array $fields
     */
    public function __construct(TypeRegistry $registry, array $fields)
    {
        $this->fields = $fields;

        parent::__construct($registry);
    }

    /**
     * Get the fields for the type.
     *
     * @return Collection
     */
    public function getFields(): Collection
    {
        $fields = collect($this->fields);

        return $fields->map(function (RootField $field) {
            return $field->toArray();
        });
    }
}
