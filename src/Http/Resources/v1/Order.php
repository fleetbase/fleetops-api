<?php

namespace Fleetbase\Http\Resources\v1;

use Fleetbase\Support\Resolve;
use Fleetbase\Http\Resources\FleetbaseResource;

class Order extends FleetbaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->public_id,
            'internal_id' => $this->internal_id,
            'customer' => Resolve::resourceForMorph($this->customer_type, $this->customer_uuid),
            'payload' => new Payload($this->payload),
            'facilitator' =>  Resolve::resourceForMorph($this->facilitator_type, $this->facilitator_uuid),
            'driver_assigned' => new Driver($this->driverAssigned),
            'tracking_number' => new TrackingNumber($this->trackingNumber),
            'purchase_rate' => new PurchaseRate($this->purchaseRate),
            'notes' => $this->notes ?? '',
            'type' => $this->type ?? null,
            'status' => $this->status,
            'pod_method' => $this->pod_method,
            'pod_required' => $this->pod_required ?? false,
            'adhoc' => $this->adhoc,
            'adhoc_distance' => (int) $this->getAdhocDistance(),
            'distance' => (int) $this->distance,
            'time' => (int) $this->time,
            'meta' => $this->meta ?? [],
            'dispatched_at' => $this->dispatched_at,
            'started_at' => $this->started_at,
            'scheduled_at' => $this->scheduled_at,
            'updated_at' => $this->updated_at,
            'created_at' => $this->created_at,
        ];
    }

    /**
     * Transform the resource into an webhook payload.
     *
     * @return array
     */
    public function toWebhookPayload()
    {
        return [
            'id' => $this->public_id,
            'internal_id' => $this->internal_id,
            'customer' => Resolve::resourceForMorph($this->customer_type, $this->customer_uuid),
            'payload' => new Payload($this->payload),
            'facilitator' =>  Resolve::resourceForMorph($this->facilitator_type, $this->facilitator_uuid),
            'driver_assigned' => new Driver($this->driverAssigned),
            'tracking_number' => new TrackingNumber($this->trackingNumber),
            'purchase_rate' => new PurchaseRate($this->purchaseRate),
            'notes' => $this->notes ?? '',
            'type' => $this->type ?? null,
            'status' => $this->status,
            'adhoc' => $this->adhoc,
            'meta' => $this->meta ?? [],
            'dispatched_at' => $this->dispatched_at,
            'started_at' => $this->started_at,
            'scheduled_at' => $this->scheduled_at,
            'updated_at' => $this->updated_at,
            'created_at' => $this->created_at,
        ];
    }
}
