<?php

namespace Bakery\Exceptions;

/**
 * Thrown when a Model adding to the Bakery Schema does not have the
 * GraphQLResource trait.
 */
class ModelNotGraphQLResource extends \RuntimeException
{
    /**
     * @param string $model The Model that does not have the trait
     */
    public function __construct(string $model)
    {
        parent::__construct("{$model} does not have the required GraphQLResource trait to register it as a GraphQL resource.");
    }
}
