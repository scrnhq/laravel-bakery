<?php

namespace Bakery\Tests\Fixtures\Types;

use Bakery\Types\Definitions\ScalarType;
use Carbon\Carbon;
use GraphQL\Error\UserError;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Utils\Utils;

class TimestampType extends ScalarType
{
    public $name = 'Timestamp';

    public $description = 'The `Timestamp` scalar type represents a datetime as
    specified by [ISO 8601](https://en.wikipedia.org/wiki/ISO_8601).';

    /**
     * Serializes an internal value to include in a response.
     *
     * @param mixed $value
     * @return mixed
     */
    public function serialize($value)
    {
        if ($value instanceof Carbon) {
            return $value->toAtomString();
        }
        throw new UserError(sprintf('Timestamp cannot represent non Carbon value: %s', Utils::printSafe($value)));
    }

    /**
     * Parses an externally provided value (query variable) to use as an input.
     *
     * @param mixed $value
     * @return mixed
     */
    public function parseValue($value)
    {
        return Carbon::parse($value);
    }

    /**
     * Parses an externally provided literal value (hardcoded in GraphQL query) to use as an input.
     *
     * @param \GraphQL\Language\AST\Node $ast
     * @return mixed
     */
    public function parseLiteral($ast)
    {
        if ($ast instanceof StringValueNode) {
            return Carbon::parse($ast->value);
        }

        return null;
    }
}
