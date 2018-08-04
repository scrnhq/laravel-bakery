<?php

namespace Bakery\Contracts;

use GraphQL\Type\Definition\Type;

interface FieldContract
{
    public function getType(): Type;
    public function toType(): Type;
    public function toField(): array;
    public function resolve($resolver);
    public function nullable(bool $value = true);
    public function collection(bool $value = true);
}
