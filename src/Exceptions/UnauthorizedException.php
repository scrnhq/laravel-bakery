<?php

namespace Bakery\Exceptions;

use Throwable;
use GraphQL\Error\UserError;

class UnauthorizedException extends UserError
{
    public function __construct($message = "This action is unauthorized.", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
