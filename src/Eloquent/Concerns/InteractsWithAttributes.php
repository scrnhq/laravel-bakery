<?php

namespace Bakery\Eloquent\Concerns;

use GraphQL\Error\UserError;

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
            try {
                $this->fillScalar($key, $value);
            } catch (\Exception $previous) {
                throw new UserError('Could not set '.$key, [
                    $key => $previous->getMessage(),
                ]);
            }
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
        $policyMethod = 'set'.studly_case($key).'Attribute';

        if (method_exists($this->policy(), $policyMethod)) {
            $this->gate->authorize($policyMethod, [$this, $value]);
        }

        return $this->setAttribute($key, $value);
    }
}
