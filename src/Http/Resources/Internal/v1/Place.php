<?php

namespace Fleetbase\Http\Resources\Internal\v1;

use Fleetbase\Http\Resources\v1\Place as PlaceResouce;
use Grimzy\LaravelMysqlSpatial\Types\Point;

class Place extends PlaceResouce
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
            'name' => $this->name,
            'location' => $this->location ?? new Point(0, 0),
            'address' => $this->address,
            'address_html' => $this->address_html,
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
            'type' => $this->type ?? null,
            'meta' => $this->meta,
            'updated_at' => $this->updated_at,
            'created_at' => $this->created_at,
        ];
    }
}
