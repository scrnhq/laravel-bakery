<?php

namespace Bakery\Eloquent\Concerns;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Gate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Access\AuthorizationException;

trait Authorizable
{
    /**
     * Determine if the current user can create a new model or throw an exception.
     *
     * @return void
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function authorizeToCreate(): void
    {
        if (! static::authorizedToCreate()) {
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
        return $this->authorized('create');
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
     * Determine if the current user can update the model.
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
     * Determine if the current user can delete the model.
     *
     * @return bool
     */
    public function authorizedToDelete(): bool
    {
        return $this->authorized('delete');
    }

    /**
     * Determine if the current user can add the given model to the model or throw an exception.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function authorizeToAdd(Model $model): void
    {
        $method = 'add'.Str::singular(class_basename($model));

        $this->authorize($method, [$model]);
    }

    /**
     * Determine if the current user can add the given model to the model.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return bool
     */
    public function authorizedToAdd(Model $model): bool
    {
        $method = 'add'.Str::singular(class_basename($model));

        return $this->authorized($method, [$model]);
    }

    /**
     * Determine if the current user can add the given model to the model or throw an exception.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function authorizeToRemove(Model $model): void
    {
        $method = 'remove'.str_singular(class_basename($model));

        $this->authorize($method, [$model]);
    }

    /**
     * Determine if the current user can add the given model to the model.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return bool
     */
    public function authorizedToRemove(Model $model): bool
    {
        $method = 'remove'.str_singular(class_basename($model));

        return $this->authorized($method, [$model]);
    }

    /**
     * Determine if the current user can attach the given model to the model.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param array|null $pivot
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function authorizeToAttach(Model $model, array $pivot = null): void
    {
        $method = 'attach'.Str::singular(class_basename($model));

        $this->authorize($method, [$model, $pivot]);
    }

    /**
     * Determine if the current user can attach the given model to the model.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param array|null $pivot
     * @return bool
     */
    public function authorizedToAttach(Model $model, array $pivot = null): bool
    {
        $method = 'attach'.Str::singular(class_basename($model));

        return $this->authorized($method, [$model, $pivot]);
    }

    /**
     * Determine if the current user can detach the given model from the model.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function authorizeToDetach(Model $model): void
    {
        $method = 'detach'.Str::singular(class_basename($model));

        $this->authorize($method, [$model]);
    }

    /**
     * Determine if the current user can detach the given model from the model.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return bool
     */
    public function authorizedToDetach(Model $model): bool
    {
        $method = 'detach'.Str::singular(class_basename($model));

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
        if (! $this->authorized($ability, $arguments)) {
            throw new AuthorizationException("Not allowed to perform {$ability} on {$this->getModelClass()}");
        }
    }

    /**
     * Determine if the current user has a given ability.
     *
     * @param string $ability
     * @param array $arguments
     * @return bool
     */
    public function authorized(string $ability, array $arguments = []): bool
    {
        $arguments = array_merge([$this->instance ?? $this->getModelClass()], $arguments);

        return Gate::check($ability, $arguments);
    }
}
