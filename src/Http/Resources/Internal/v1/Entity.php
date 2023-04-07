<?php

namespace Fleetbase\FleetOps\Http\Resources\Internal\v1;

use Fleetbase\FleetOps\Http\Resources\v1\Entity as EntityResource;
use Fleetbase\Support\Resolve;

class Entity extends EntityResource
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
            'uuid' => $this->uuid,
            'id' => $this->public_id,
            'public_id' => $this->public_id,
            'internal_id' => $this->internal_id,
            'destination_uuid' => $this->destination_uuid,
            'tracking_number_uuid' => $this->tracking_number_uuid,
            'name' => $this->name,
            'type' => $this->type,
            'customer' => Resolve::resourceForMorph($this->customer_type, $this->customer_uuid),
            'description' => $this->description ?? null,
            'photo_url' => $this->photo_url ?? null,
            'tracking' => $this->tracking ?? null,
            'length' => $this->length ?? null,
            'width' => $this->width ?? null,
            'height' => $this->height ?? null,
            'dimensions_unit' => $this->dimensions_unit ?? null,
            'weight' => $this->weight ?? null,
            'weight_unit' => $this->weight_unit ?? null,
            'declared_value' => $this->declared_value ?? null,
            'price' => $this->price ?? null,
            'sale_price' => $this->sale_price ?? null,
            'sku' => $this->sku ?? null,
            'currency' => $this->currency,
            'meta' => $this->meta ?? [],
            'updated_at' => $this->updated_at,
            'created_at' => $this->created_at,
        ];
    }
}