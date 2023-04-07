<?php

namespace Fleetbase\FleetOps\Events;

class OrderDispatched extends ResourceLifecycleEvent
{
    /**
     * The event name.
     *
     * @var string
     */
    public $eventName = 'dispatched';
}
