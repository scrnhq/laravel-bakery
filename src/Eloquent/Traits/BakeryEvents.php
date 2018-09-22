<?php

namespace Bakery\Eloquent\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;

trait BakeryEvents
{
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
    public static function bootBakeryEvents()
    {
        Event::listen('eloquent.booted: '.static::class, function (Model $model) {
            $model->addObservableEvents(['persisting', 'persisted']);
        });
    }

    /**
     * Fire an event when the model is being persisted.
     *
     * @return void
     */
    public function firePersistingEvent()
    {
        $this->fireModelEvent('persisting');
    }

    /**
     * Fire an event when the model is persisted.
     *
     * @return void
     */
    public function firePersistedEvent()
    {
        $this->fireModelEvent('persisted');
    }
}
