<?php

namespace Bakery;

/**
 * Verify that the contents of the variable can be called
 * as a function, but is not a global function.
 *
 * @param  callable|mixed  $var
 * @return bool
 */
function is_callable_tuple($var)
{
    return is_callable($var) && is_object($var);
}
