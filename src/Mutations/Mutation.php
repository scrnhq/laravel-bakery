<?php

namespace Bakery\Mutations;

use Bakery\Support\Field;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Auth\Access\AuthorizationException;

abstract class Mutation extends Field
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
    public function name(): string
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
     * @param  string $ability
     * @param  array $args
     * @return void
     * @throws AuthorizationException
     */
    protected function authorize(string $ability, $args)
    {
        if (! is_array($args)) {
            $args = [$args];
        }

        $user = auth()->user();

        $allowed = optional($user)->can($ability, $args);

        if (! $allowed) {
            $model = $args[0];
            $model = is_object($model) ? get_class($model) : $model;

            throw new AuthorizationException(
                'Not allowed to perform "'.$ability.'" on '.$model
            );
        }
    }
}
