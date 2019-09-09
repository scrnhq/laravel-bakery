<?php

namespace Bakery\Exceptions;

use Throwable;
use GraphQL\Error\UserError;

class UnauthorizedException extends UserError
{
    public function __construct($message = null, $code = null, Throwable $previous = null)
    {
        parent::__construct($message ?: 'This action is unauthorized.', 0, $previous);

        $this->code = $code ?: 0;
    }
}
