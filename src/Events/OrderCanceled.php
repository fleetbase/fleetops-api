<?php

namespace Fleetbase\Events;

class OrderCanceled extends ResourceLifecycleEvent
{
    /**
     * The event name.
     *
     * @var string
     */
    public $eventName = 'canceled';
}
