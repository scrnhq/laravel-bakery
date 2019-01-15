<?php

namespace Bakery\Eloquent\Concerns;

use Illuminate\Support\Collection;

trait InteractsWithAttributes
{
    /**
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $instance;

    /**
     * @return \Illuminate\Support\Collection
     */
    abstract protected function getFields(): Collection;

    /**
     * Fill the scalars in the model.
     *
     * @param array $scalars
     */
    protected function fillScalars(array $scalars)
    {
        foreach ($scalars as $key => $value) {
            $this->instance->setAttribute($key, $value);
        }
    }

    /**
     * Check the policies for the scalars in the model.
     *
     * @param array $scalars
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    protected function checkScalars(array $scalars)
    {
        foreach ($scalars as $key => $value) {
            /** @var \Bakery\Fields\Field $field */
            $field = $this->getFields()->get($key);

            $field->authorizeToStore($this->instance, $key, $value);
        }
    }
}
