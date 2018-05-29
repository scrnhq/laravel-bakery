<?php

namespace Bakery\Eloquent\Concerns;

trait QueuesTransactions
{
    /**
     * The queue of closures that should be called
     * after the entity is persisted.
     *
     * @var array
     */
    private $transactionQueue = [];

    /**
     * Persist the DB transactions that are queued.
     *
     * @return void
     */
    public function persistQueuedDatabaseTransactions()
    {
        foreach ($this->transactionQueue as $key => $closure) {
            $closure($this);
            unset($this->transactionQueue[$key]);
        }
    }
}
