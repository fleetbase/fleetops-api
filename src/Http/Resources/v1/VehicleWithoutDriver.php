<?php

namespace Fleetbase\FleetOps\Http\Resources\v1;

use Fleetbase\Http\Resources\FleetbaseResource;

class VehicleWithoutDriver extends FleetbaseResource
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
            'vin' => $this->vin ?? null,
            'photo' => $this->photoUrl ?? null,
            'make' => $this->make ?? null,
            'model' => $this->model ?? null,
            'year' => $this->year ?? null,
            'trim' => $this->trim ?? null,
            'type' => $this->type ?? null,
            'plate_number' => $this->plate_number ?? null,
            'vin_data' => $this->vin_data,
            'status' => $this->status,
            'meta' => $this->meta,
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
            'vin' => $this->vin ?? null,
            'photo' => $this->photoUrl ?? null,
            'make' => $this->make ?? null,
            'model' => $this->model ?? null,
            'year' => $this->year ?? null,
            'trim' => $this->trim ?? null,
            'type' => $this->type ?? null,
            'plate_number' => $this->plate_number ?? null,
            'vin_data' => $this->vin_data,
            'status' => $this->status,
            'meta' => $this->meta,
            'updated_at' => $this->updated_at,
            'created_at' => $this->created_at,
        ];
    }
}
