<?php

namespace Bakery\Exceptions;

use GraphQL\Error\UserError;
use Throwable;

class UnauthorizedException extends UserError
{
    public function __construct($message = null, $code = null, Throwable $previous = null)
    {
        parent::__construct($message ?: 'This action is unauthorized.', 0, $previous);

        $this->code = $code ?: 0;
    }
}
