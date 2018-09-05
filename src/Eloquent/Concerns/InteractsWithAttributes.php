<?php

namespace Bakery\Eloquent\Concerns;

trait InteractsWithAttributes
{
    /**
     * Fill the scalars in the model.
     *
     * @param array $scalars
     */
    protected function fillScalars(array $scalars)
    {
        foreach ($scalars as $key => $value) {
            $this->setAttribute($key, $value);
        }
    }

    /**
     * Check the policies for the scalars in the model.
     *
     * @param array $scalars
     */
    protected function checkScalars(array $scalars)
    {
        foreach ($scalars as $key => $value) {
            $field = $this->getSchema()->getFields()->get($key);
            $field->checkStorePolicy($this->getModel(), $key, $value);
        }
    }
}
