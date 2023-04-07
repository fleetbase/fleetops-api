<?php

namespace Fleetbase\FleetOps\Events;

class OrderCompleted extends ResourceLifecycleEvent
{
    /**
     * The event name.
     *
     * @var string
     */
    public $eventName = 'completed';
}
