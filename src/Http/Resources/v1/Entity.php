<?php

namespace Fleetbase\FleetOps\Http\Resources\v1;

use Fleetbase\Support\Resolve;
use Fleetbase\Http\Resources\FleetbaseResource;

class Entity extends FleetbaseResource
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
            'name' => $this->name,
            'type' => $this->type ?? null,
            'destination' => $this->destination ? $this->destination->public_id : null,
            'customer' => Resolve::resourceForMorph($this->customer_type, $this->customer_uuid),
            'tracking_number' => new TrackingNumber($this->trackingNumber),
            'description' => $this->description ?? null,
            'photo_url' => $this->photo_url ?? null,
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
            'currency' => $this->currency ?? null,
            'meta' => $this->meta ?? [],
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
            'name' => $this->name,
            'type' => $this->type ?? null,
            'destination' => $this->destination ? $this->destination->public_id : null,
            'customer' => Resolve::resourceForMorph($this->customer_type, $this->customer_uuid),
            'tracking_number' => new TrackingNumber($this->trackingNumber),
            'description' => $this->description ?? null,
            'photo_url' => $this->photo_url ?? null,
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
            'currency' => $this->currency ?? null,
            'meta' => $this->meta ?? [],
            'updated_at' => $this->updated_at,
            'created_at' => $this->created_at,
        ];
    }
}
