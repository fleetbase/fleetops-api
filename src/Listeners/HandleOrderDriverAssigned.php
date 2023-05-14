<?php

namespace Fleetbase\FleetOps\Listeners;

use Fleetbase\FleetOps\Events\OrderDriverAssigned;
use Fleetbase\FleetOps\Models\Driver;
use Fleetbase\FleetOps\Models\Order;
use Fleetbase\Notifications\OrderAssigned;
use Fleetbase\Notifications\StorefrontOrderDriverAssigned;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
// use Illuminate\Support\Facades\Log;

class HandleOrderDriverAssigned implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(OrderDriverAssigned $event)
    {
        /** @var \Fleetbase\FleetOps\Models\Order $order */
        $order = $event->getModelRecord();

        // halt if unable to resolve order record from event
        if (!$order instanceof Order) {
            return;
        }

        /** @var \Fleetbase\Models\Driver */
        $driver = Driver::where('uuid', $order->driver_assigned_uuid)->withoutGlobalScopes()->first();
        $order->setRelation('driverAssigned', $driver);

        // if storefront order notify customer driver has been addigned
        if ($order->hasMeta('storefront_id')) {
            $order->load(['customer']);
            $order->customer->notify(new StorefrontOrderDriverAssigned($order));
        }

        // notify driver order has been assigned - only if order is not adhoc
        if ($driver && $order->adhoc === false) {
            $driver->notify(new OrderAssigned($order));
        }
    }
}
