<?php

if (! function_exists('class_uses_deep')) {
    /**
     * Recursively get all the traits a class uses.
     * Credits to:
     * https://stackoverflow.com/questions/46218000/how-to-check-if-a-class-uses-a-trait-in-php.
     *
     * @param  string   $class
     * @param  bool  $autoload
     * @return array
     */
    function class_uses_deep($class, $autoload = true)
    {
        $traits = [];

        // Get traits of all parent classes
        do {
            $traits = array_merge(class_uses($class, $autoload), $traits);
        } while ($class = get_parent_class($class));

        // Get traits of all parent traits
        $traitsToSearch = $traits;

        while (! empty($traitsToSearch)) {
            $newTraits = class_uses(array_pop($traitsToSearch), $autoload);
            $traits = array_merge($newTraits, $traits);
            $traitsToSearch = array_merge($newTraits, $traitsToSearch);
        }

        foreach ($traits as $trait => $same) {
            $traits = array_merge(class_uses($trait, $autoload), $traits);
        }

        return array_values(array_unique($traits));
    }
}

if (! function_exists('is_callable_tuple')) {
    /**
     * Verify that the contents of the variable can be called
     * as a function, but is not a global function.
     *
     * @param callable|mixed $var
     * @return bool
     */
    function is_callable_tuple($var)
    {
        return is_callable($var) && is_object($var);
    }
}