<?php

namespace Bakery\Eloquent\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;

trait BakeryTransactionalAware
{
    protected $inTransaction = false;

    /**
     * Fire the given event for the model.
     *
     * @param  string  $event
     * @param  bool  $halt
     * @return mixed
     */
    abstract protected function fireModelEvent($event, $halt = true);

    /**
     * The "booting" method of the Bakery events trait.
     *
     * @return void
     */
    public static function bootBakeryTransactionalAware()
    {
        Event::listen('eloquent.booted: '.static::class, function (Model $model) {
            $model->addObservableEvents(['persisting', 'persisted']);
        });
    }

    /**
     * Start the transaction for this model.
     *
     * @return void
     */
    public function startTransaction()
    {
        $this->inTransaction = true;
        $this->fireModelEvent('persisting');
    }

    /**
     * End the transaction for this model.
     *
     * @return void
     */
    public function endTransaction()
    {
        $this->inTransaction = false;
        $this->fireModelEvent('persisted');
    }

    /**
     * Return if the model is currently in transaction.
     *
     * @return bool
     */
    public function inTransaction(): bool
    {
        return $this->inTransaction;
    }
}
