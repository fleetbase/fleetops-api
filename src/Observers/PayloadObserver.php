<?php

namespace Fleetbase\FleetOps\Observers;

use Fleetbase\FleetOps\Models\Payload;
use Fleetbase\Support\Utils;

class PayloadObserver
{
    /**
     * Handle the Payload "creating" event.
     *
     * @param  \Fleetbase\FleetOps\Models\Payload  $payload
     * @return void
     */
    public function created(Payload $payload)
    {
        // load the order
        $order = $payload->load('order')->order;

        if ($order) {
            $order->setRelation('payload', $payload);
            $order->setDistanceAndTime();
        }
    }
}
