<?php

namespace Fleetbase\FleetOps\Listeners;

use Fleetbase\FleetOps\Events\OrderCanceled;
use Fleetbase\FleetOps\Models\Driver;
use Fleetbase\FleetOps\Notifications\OrderCanceled as OrderCanceledNotification;

class HandleOrderCanceled
{
    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(OrderCanceled $event)
    {
        /** @var \Fleetbase\FleetOps\Models\Order $order */
        $order = $event->getModelRecord();
        $location = $order->getLastLocation();

        // update order activity 
        $order->setStatus('canceled');
        $order->createActivity('Order canceled', 'Order was canceled', $location, $order->status);

        if ($order->isIntegratedVendorOrder()) {
            $order->facilitator->provider()->callback('onCanceled', $order);
        }

        // notify driver assigned order was canceled
        if ($order->hasDriverAssigned) {
            /** @var \Fleetbase\Models\Driver */
            $driver = Driver::where('uuid', $order->driver_assigned_uuid)->withoutGlobalScopes()->first();

            if ($driver) {
                $driver->notify(new OrderCanceledNotification($order));
            }
        }
    }
}