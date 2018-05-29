<?php

namespace Bakery\Types;

use Bakery\Utils\Utils;
use Illuminate\Database\Eloquent\Model;

class CollectionOrderByType extends ModelAwareEnumType
{
    /**
     * Get the name of the Collection Order By Type.
     *
     * @return string
     */
    protected function name(): string
    {
        return Utils::typename($this->model->getModel()) . 'OrderBy';
    }

    /**
     * Return the fields for the collection filter type.
     *
     * @return array
     */
    public function values(): array
    {
        $values = [];

        foreach ($this->model->fields() as $name => $type) {
            $values[] = $name . '_ASC';
            $values[] = $name . '_DESC';
        }

        return $values;
    }
}
