<?php

namespace Bakery\Eloquent;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

trait Authorizable
{
    /**
     * Determine if the current user can create a new model of throw an exception.
     *
     * @return void
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function authorizeToCreate(): void
    {
        if ($this->authorizedToCreate()) {
            throw new AuthorizationException("Not allowed to perform create on {$this->getModelClass()}");
        }
    }

    /**
     * Determine if the current user can create a new model.
     *
     * @return bool
     */
    public function authorizedToCreate(): bool
    {
        return Gate::check('create', $this->getModelClass());
    }

    /**
     * Determine if the current user can update the model or throw an exception.
     *
     * @return void
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function authorizeToUpdate(): void
    {
        $this->authorize('update');
    }

    /**
     *  Determine if the current user can update the model.
     *
     * @return bool
     */
    public function authorizedToUpdate(): bool
    {
        return $this->authorized('update');
    }

    /**
     * Determine if the current user can delete the model or throw an exception.
     *
     * @return void
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function authorizeToDelete(): void
    {
        $this->authorize('delete');
    }

    /**
     *  Determine if the current user can delete the model.
     *
     * @return bool
     */
    public function authorizedToDelete(): bool
    {
        return $this->authorized('update');
    }

    /**
     * Determine if the current user can add the given model to the model.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return bool
     */
    public function authorizedToAdd(Model $model): bool
    {
        $method = 'add'.str_singular(class_basename($model));

        return $this->authorized($method, $model);
    }

    /**
     * Determine if the current user can attach the given model to the model.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return bool
     */
    public function authorizedToAttach(Model $model): bool
    {
        $method = 'attach'.str_singular(class_basename($model));

        return $this->authorized($method, $model);
    }

    /**
     * Determine if the current user can detach the given model from the model.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return bool
     */
    public function authorizedToDetach(Model $model): bool
    {
        $method = 'detach'.str_singular(class_basename($model));

        return $this->authorized($method, [$model]);
    }

    /**
     * Determine if the current user has a given ability or throw an exception.
     *
     * @param string $ability
     * @param array|null $arguments
     * @return void
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function authorize(string $ability, $arguments = []): void
    {
        if (!$this->authorized($ability, $arguments)) {
            throw new AuthorizationException("Not allowed to perform {$ability} on {$this->getModelClass()}");
        }
    }

    /**
     * Determine if the current user has a given ability.
     *
     * @param string $ability
     * @param array|null $arguments
     * @return bool
     */
    public function authorized(string $ability, $arguments = []): bool
    {
        return Gate::check($ability, $arguments ? array_merge($this->instance, $arguments) : $this->instance);
    }
}
