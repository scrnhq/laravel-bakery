<?php

namespace Bakery\Errors;

use GraphQL\Error\Error;
use GraphQL\Error\ClientAware;

class ValidationError extends Error implements ClientAware
{
    /**
     * The validator instance.
     *
     * @var \Illuminate\Contracts\Validation\Validator
     */
    public $validator;

    /**
     * Create a new exception instance.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     */
    public function __construct($validator)
    {
        parent::__construct($validator->errors()->first());

        $this->validator = $validator;
        $this->extensions['validation'] = $validator->errors();
    }

    /**
     * Returns true when exception message is safe to be displayed to a client.
     *
     * @return bool
     *
     * @api
     */
    public function isClientSafe()
    {
        return true;
    }

    /**
     * Returns string describing a category of the error.
     *
     * Value "graphql" is reserved for errors produced by query parsing or validation, do not use it.
     *
     * @return string
     *
     * @api
     */
    public function getCategory()
    {
        return 'user';
    }
}
