<?php

namespace Bakery\Events;

class BakeryModelSaved
{
    protected $model;

    /**
     * @param \Bakery\Eloquent\Mutable $model
     */
    public function __construct($model)
    {
        $this->model = $model;
    }

    /**
     * @return \Bakery\Eloquent\Mutable
     */
    public function getModel()
    {
        return $this->model;
    }
}
