<?php

namespace Bakery\Exceptions;

use GraphQL\Error\UserError;
use Illuminate\Contracts\Support\MessageBag;

class ValidationException extends UserError
{
    public function __construct(MessageBag $bag)
    {
        parent::__construct($bag->first());
    }
}
