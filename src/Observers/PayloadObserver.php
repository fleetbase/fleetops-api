<?php

namespace Fleetbase\FleetOps\Observers;

use Fleetbase\FleetOps\Models\Payload;

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
    /**
     * Handle the Payload "updating" event.
     *
     * @param  \Fleetbase\FleetOps\Models\Payload  $payload
     * @return void
     */
    public function updating(Payload $payload)
    {
        $waypoints = request()->array('paylad.waypoints');
        $payload->updateWaypoints($waypoints);
        $payload->flushOrderCache();
    }
}
