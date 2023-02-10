<?php

namespace Fleetbase\Http\Resources\v1;

use Fleetbase\Http\Resources\FleetbaseResource;
use Fleetbase\Support\Http;
use Grimzy\LaravelMysqlSpatial\Types\Point;
use Illuminate\Support\Arr;

class ServiceArea extends FleetbaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $serviceArea = [
            'id' => $this->public_id,
            'name' => $this->name,
            'type' => $this->type,
            'location' => $this->location ?? new Point(0, 0),
            'status' => $this->status,
            'updated_at' => $this->updated_at,
            'created_at' => $this->created_at,
        ];

        if (Http::isInternalRequest()) {
            $serviceArea = Arr::insertAfterKey($serviceArea, ['uuid' => $this->uuid, 'public_id' => $this->public_id], 'id');
        }

        return $serviceArea;
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
            'type' => $this->type,
            'location' => $this->location ?? new Point(0, 0),
            'status' => $this->status,
            'updated_at' => $this->updated_at,
            'created_at' => $this->created_at,
        ];
    }
}
