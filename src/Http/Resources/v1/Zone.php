<?php

namespace Fleetbase\FleetOps\Http\Resources\v1;

use Fleetbase\Http\Resources\FleetbaseResource;
use Fleetbase\Support\Utils;

class Zone extends FleetbaseResource
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
            'name' => $this->name,
            'description' => $this->description ?? null,
            'coordinates' => $this->coordinates ?? [],
            'color' => $this->color,
            'stroke_color' => $this->stroke_color,
            'status' => $this->status,
            'service_area' => new ServiceArea($this->serviceArea),
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
            'name' => $this->name,
            'description' => $this->description ?? null,
            'coordinates' => $this->coordinates ?? [],
            'color' => $this->color,
            'stroke_color' => $this->stroke_color,
            'status' => $this->status,
            'service_area' => new ServiceArea($this->serviceArea),
            'updated_at' => $this->updated_at,
            'created_at' => $this->created_at,
        ];
    }
}
