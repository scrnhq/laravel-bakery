<?php

namespace Bakery\Queries;

use Bakery\Utils\Utils;
use Bakery\Concerns\ModelAware;
use Bakery\Eloquent\ModelSchema;
use GraphQL\Type\Definition\Type;
use Bakery\Support\Facades\Bakery;
use GraphQL\Type\Definition\ListOfType;

abstract class EntityQuery extends Query
{
    use ModelAware;
    
    /**
     * Get the name of the EntityQuery.
     *
     * @return string
     */
    protected function name(): string
    {
        return Utils::single($this->model);
    }

    /**
     * The type of the Query.
     *
     * @return Type
     */
    public function type()
    {
        return Bakery::type($this->schema->typename());
    }

    /**
     * The arguments for the Query.
     *
     * @return array
     */
    public function args(): array
    {
        $args = $this->model->getLookupFields();

        foreach ($this->model->getRelations() as $relation => $field) {
            $type = $field['type'];
            if ($type instanceof ListofType) {
                continue;
            }

            $lookupTypeName = Type::getNamedType($type)->name.'LookupType';
            $args[$relation] = Bakery::type($lookupTypeName);
        }

        return $args;
    }
}
