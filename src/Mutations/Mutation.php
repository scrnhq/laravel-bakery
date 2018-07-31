<?php

namespace Bakery\Mutations;

use Bakery\Support\Field;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class Mutation extends Field
{
    /**
     * Instance of the gate class.
     *
     * @var Gate
     */
    protected $gate;

    /**
     * Get the name of the Mutation, if no name is specified fall back
     * on a name based on the class name.
     *
     * @return string
     */
    protected function name(): string
    {
        if (property_exists($this, 'name')) {
            return $this->name;
        }

        return camel_case(str_before(class_basename($this), 'Mutation'));
    }

    /**
     * Return an instance of the Gate class.
     *
     * @return Gate
     */
    protected function gate(): Gate
    {
        if (! isset($this->gate)) {
            $this->gate = app(Gate::class);
        }

        return $this->gate;
    }

    /**
     * Authorize an action.
     *
     * @param  string $policyMethod
     * @param  array $args
     * @return void
     */
    protected function authorize(string $policyMethod, $instance, ...$other)
    {
        $allowed = $this->gate()->allows($policyMethod, $instance, ...$other);

        if (!$allowed) {
            throw new AuthorizationException(
                'Not allowed to perform '.$policyMethod.' on '.get_class($instance)
            );
        }
    }
}
