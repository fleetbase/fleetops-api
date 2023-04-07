<?php

namespace Fleetbase\FleetOps\Events;

class OrderDriverAssigned extends ResourceLifecycleEvent
{
    /**
     * The event name.
     *
     * @var string
     */
    public $eventName = 'driver_assigned';
}
