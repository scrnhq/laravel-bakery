<?php

namespace Bakery\Exceptions;

use Exception;

class PaginationMaxCountExceededException extends Exception
{
    /**
     * @param string $model The Model that does not have the trait
     */
    public function __construct(int $maxCount)
    {
        parent::__construct("The pagination max count of [{$maxCount}] is exceeded.");
    }
}
