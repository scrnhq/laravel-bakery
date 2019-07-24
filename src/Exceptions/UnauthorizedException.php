<?php

namespace Bakery\Exceptions;

use GraphQL\Error\UserError;

class UnauthorizedException extends UserError
{
    protected $message = 'You are not authorized to perform this action.';
}
