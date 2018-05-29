<?php

namespace Bakery\Types;

use Bakery\Utils\Utils;
use Bakery\Support\Facades\Bakery;

class CollectionRootSearchType extends ModelAwareInputType
{
    /**
     * Get the name of the Collection Root Search Type.
     *
     * @return string
     */
    protected function name(): string
    {
        return Utils::typename($this->model->getModel()).'RootSearch';
    }

    /**
     * Return the fields for the collection filter type.
     *
     * @return array
     */
    public function fields(): array
    {
        return [
            'query' => Bakery::nonNull(Bakery::string()),
            'fields' => Bakery::nonNull(Bakery::type(Utils::typename(class_basename($this->model->getModel()).'Search'))),
        ];
    }
}
