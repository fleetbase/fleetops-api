<?php

namespace Fleetbase\FleetOps\Http\Resources\v1;

use Fleetbase\Http\Resources\FleetbaseResource;
use Fleetbase\Support\Resolve;
use Illuminate\Support\Arr;
use Grimzy\LaravelMysqlSpatial\Types\Point;

class Place extends FleetbaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $place = [
            'id' => $this->public_id ?? null,
            'name' => $this->name,
            'location' => $this->location ?? new Point(0, 0),
            'address' => $this->address,
            'street1' => $this->street1 ?? null,
            'street2' => $this->street2 ?? null,
            'city' => $this->city ?? null,
            'province' => $this->province ?? null,
            'postal_code' => $this->postal_code ?? null,
            'neighborhood' => $this->neighborhood ?? null,
            'district' => $this->district ?? null,
            'building' => $this->building ?? null,
            'security_access_code' => $this->security_access_code ?? null,
            'country' => $this->country ?? null,
            'phone' => $this->phone ?? null,
            'owner' => Resolve::resourceForMorph($this->owner_type, $this->owner_uuid),
            'type' => $this->type ?? null,
            'meta' => $this->meta ?? [],
            'updated_at' => $this->updated_at,
            'created_at' => $this->created_at
        ];

        if ($this->trackingNumber) {
            $place = Arr::insertAfterKey($place, ['tracking_number' => $this->tracking_number], 'owner');
        }

        return $place;
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
            'name' => $this->name,
            'latitude' => $this->latitude ?? null,
            'longitude' => $this->longitude ?? null,
            'street1' => $this->street1 ?? null,
            'street2' => $this->street2 ?? null,
            'city' => $this->city ?? null,
            'province' => $this->province ?? null,
            'postal_code' => $this->postal_code ?? null,
            'neighborhood' => $this->neighborhood ?? null,
            'district' => $this->district ?? null,
            'building' => $this->building ?? null,
            'security_access_code' => $this->security_access_code ?? null,
            'country' => $this->country ?? null,
            'phone' => $this->phone ?? null,
            'owner' => Resolve::resourceForMorph($this->owner_type, $this->owner_uuid),
            'type' => $this->type ?? null,
            'meta' => $this->meta ?? [],
            'updated_at' => $this->updated_at,
            'created_at' => $this->created_at,
        ];
    }
}
