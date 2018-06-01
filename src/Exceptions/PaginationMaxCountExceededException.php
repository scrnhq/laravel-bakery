<?php

namespace Bakery\Exceptions;

use Exception;

class PaginationMaxCountExceededException extends Exception
{
    /**
     * @param int $maxCount
     */
    public function __construct(int $maxCount)
    {
        parent::__construct("The pagination max count of [{$maxCount}] is exceeded.");
    }
}
