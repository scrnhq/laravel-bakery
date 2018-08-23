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
            $this->fillScalar($key, $value);
        }
    }

    /**
     * Fill a scalar.
     *
     * @param string $key
     * @param mixed $value
     * @return $this;
     */
    protected function fillScalar(string $key, $value)
    {
        $field = $this->getSchema()->getFields()->get($key);
        $field->checkStorePolicy($this->getModel(), $key);

        return $this->setAttribute($key, $value);
    }
}
