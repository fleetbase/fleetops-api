<?php

namespace Fleetbase\Events;

class OrderDispatched extends ResourceLifecycleEvent
{
    /**
     * The event name.
     *
     * @var string
     */
    public $eventName = 'dispatched';
}
