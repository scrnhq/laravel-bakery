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
    public $queue = [];

    /**
     * Add a closure to the queue.
     *
     * @param \Closure $closure
     */
    protected function queue(\Closure $closure) {
        $this->queue[] = $closure;
    }

    /**
     * Persist the DB transactions that are queued.
     *
     * @return void
     */
    public function persistQueuedDatabaseTransactions()
    {
        foreach ($this->queue as $key => $closure) {
            $closure($this);
            unset($this->queue[$key]);
        }
    }
}
