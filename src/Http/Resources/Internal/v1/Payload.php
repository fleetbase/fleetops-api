<?php

namespace Fleetbase\Http\Resources\Internal\v1;

use Fleetbase\Http\Resources\v1\Payload as PayloadResource;

class Payload extends PayloadResource
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
            'uuid' => $this->uuid,
            'current_waypoint_uuid' => $this->current_waypoint_uuid,
            'pickup_uuid' => $this->pickup_uuid,
            'dropoff_uuid' => $this->dropoff_uuid,
            'return_uuid' => $this->return_uuid,
            'pickup' => $this->pickup ? new Place($this->pickup) : null,
            'dropoff' => $this->dropoff ? new Place($this->dropoff) : null,
            'return' => $this->return ? new Place($this->return) : null,
            'waypoints' => Waypoint::collection($this->waypointMarkers ?? []),
            'entities' => Entity::collection($this->entities ?? []),
            'cod_amount' => $this->cod_amount ?? null,
            'cod_currency' => $this->cod_currency ?? null,
            'cod_payment_method' => $this->cod_payment_method ?? null,
            'meta' => $this->meta,
            'updated_at' => $this->updated_at,
            'created_at' => $this->created_at,
        ];
    }
}
