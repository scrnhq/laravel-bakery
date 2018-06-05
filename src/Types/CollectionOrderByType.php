<?php

namespace Bakery\Types;

use Bakery\Concerns\ModelAware;

class CollectionOrderByType extends EnumType
{
    use ModelAware;

    /**
     * Get the name of the Collection Order By Type.
     *
     * @return string
     */
    protected function name(): string
    {
        return $this->schema->typename().'OrderBy';
    }

    /**
     * Return the fields for the collection filter type.
     *
     * @return array
     */
    public function values(): array
    {
        $values = [];

        foreach ($this->schema->getFields() as $name => $type) {
            $values[] = $name.'_ASC';
            $values[] = $name.'_DESC';
        }

        return $values;
    }
}
