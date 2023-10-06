<?php

namespace Fleetbase\FleetOps\Http\Resources\v1;

use Fleetbase\Http\Resources\FleetbaseResource;
use Fleetbase\Support\Http;

class Fleet extends FleetbaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return array_merge(
            $this->getInternalIds(),
            [
                'id' => $this->when(Http::isInternalRequest(), $this->id, $this->public_id),
                'uuid' => $this->when(Http::isInternalRequest(), $this->uuid),
                'public_id' => $this->when(Http::isInternalRequest(), $this->public_id),
                'name' => $this->name,
                'task' => $this->task ?? null,
                'status' => $this->status ?? null,
                'drivers_count' => $this->when(Http::isInternalRequest(), $this->drivers_count),
                'drivers_online_count' => $this->when(Http::isInternalRequest(), $this->drivers_online_count),
                'service_area' => $this->whenLoaded('serviceArea', new ServiceArea($this->serviceArea)),
                'zone' => $this->whenLoaded('zone', new Zone($this->zone)),
                'vendor' => $this->whenLoaded('vendor', new Vendor($this->vendor)),
                'parent_fleet' => $this->whenLoaded('parent_fleet', new self($this->parent_fleet)),
                'drivers' => $this->whenLoaded('drivers', Driver::collection($this->drivers()->without(['driverAssigned'])->with(Http::isInternalRequest() ? ['jobs'] : [])->get())),
                'vehicles' => $this->whenLoaded('vehicles', Vehicle::collection($this->vehicles)),
                'updated_at' => $this->updated_at,
                'created_at' => $this->created_at,
            ]
        );
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
            'name' => $this->name,
            'task' => $this->task ?? null,
            'status' => $this->status ?? null,
            'service_area' => $this->when($this->serviceArea, data_get($this, 'serviceArea.public_id')),
            'zone' => $this->when($this->zone, data_get($this, 'zone.public_id')),
            'updated_at' => $this->updated_at,
            'created_at' => $this->created_at,
        ];
    }
}
