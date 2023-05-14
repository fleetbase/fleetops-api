<?php

namespace Fleetbase\FleetOps\Http\Resources\v1;

use Fleetbase\Support\Resolve;
use Fleetbase\Http\Resources\FleetbaseResource;
use Fleetbase\Support\Http;

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
            'id' => $this->when(Http::isInternalRequest(), $this->id, $this->public_id),
            'uuid' => $this->when(Http::isInternalRequest(), $this->uuid),
            'public_id' => $this->when(Http::isInternalRequest(), $this->public_id),
            'internal_id' => $this->internal_id,
            'transaction_uuid' => $this->when(Http::isInternalRequest(), $this->transaction_uuid),
            'customer_uuid' => $this->when(Http::isInternalRequest(), $this->customer_uuid),
            'facilitator_uuid' => $this->when(Http::isInternalRequest(), $this->facilitator_uuid),
            'payload_uuid' => $this->when(Http::isInternalRequest(), $this->payload_uuid),
            'route_uuid' => $this->when(Http::isInternalRequest(), $this->route_uuid),
            'purchase_rate_uuid' => $this->when(Http::isInternalRequest(), $this->purchase_rate_uuid),
            'tracking_number_uuid' => $this->when(Http::isInternalRequest(), $this->tracking_number_uuid),
            'driver_assigned_uuid' => $this->when(Http::isInternalRequest(), $this->driver_assigned_uuid),
            'service_quote_uuid' => $this->when(Http::isInternalRequest(), $this->service_quote_uuid),
            'has_driver_assigned' => $this->when(Http::isInternalRequest(), $this->has_driver_assigned),
            'is_scheduled' => $this->when(Http::isInternalRequest(), $this->is_scheduled),
            'customer' => Resolve::resourceForMorph($this->customer_type, $this->customer_uuid),
            'payload' => new Payload($this->payload),
            'facilitator' =>  Resolve::resourceForMorph($this->facilitator_type, $this->facilitator_uuid),
            'driver_assigned' => new Driver($this->driverAssigned()->without(['jobs', 'currentJob', 'driverAssigned'])->first()),
            'tracking_number' => new TrackingNumber($this->trackingNumber),
            'tracking_statuses' => $this->whenLoaded('trackingStatuses', TrackingStatus::collection($this->trackingStatuses)),
            'purchase_rate' => new PurchaseRate($this->purchaseRate),
            'notes' => $this->notes,
            'type' => $this->type,
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
