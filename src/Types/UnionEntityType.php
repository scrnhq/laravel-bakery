<?php

namespace Bakery\Types;

use Bakery\Utils\Utils;
use Bakery\Support\Facades\Bakery;
use Bakery\Eloquent\Introspectable;
use Bakery\Types\Definitions\UnionType;
use GraphQL\Type\Definition\ResolveInfo;

class UnionEntityType extends UnionType
{
    protected $definitions;

    /**
     * Construct a new union entity type.
     *
     * @param array $definitions
     */
    public function __construct(array $definitions = [])
    {
        if (isset($definitions)) {
            $this->definitions = $definitions;
        }

        Utils::invariant(
            ! empty($this->definitions),
            'No definitions defined on "'.get_class($this).'"'
        );

        foreach ($this->definitions as $definition) {
            $schema = resolve($definition);
            Utils::invariant(
                Utils::usesTrait($schema, Introspectable::class),
                class_basename($schema).' does not use the '.Introspectable::class.' trait.'
            );
        }
    }

    public function types(): array
    {
        return collect($this->definitions)->map(function (string $definition) {
            return Bakery::type(resolve($definition)->typename());
        })->toArray();
    }

    /**
     * Receives $value from resolver of the parent field and returns concrete Object Type for this $value.
     *
     * @param $value
     * @param $context
     * @param ResolveInfo $info
     * @return mixed
     */
    public function resolveType($value, $context, ResolveInfo $info)
    {
        return Bakery::resolveDefinitionType($value);
    }
}
