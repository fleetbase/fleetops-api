<?php

namespace Fleetbase\FleetOps\Http\Resources\v1;

use Fleetbase\Http\Resources\FleetbaseResource;
use Fleetbase\Support\Http;
use Illuminate\Support\Arr;

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
        $fleet = [
            'id' => $this->public_id,
            'name' => $this->name,
            'task' => $this->task ?? null,
            'status' => $this->status ?? null,
            'service_area' => new ServiceArea($this->serviceArea),
            'updated_at' => $this->updated_at,
            'created_at' => $this->created_at,
        ];

        if (Http::isInternalRequest()) {
            $fleet = Arr::insertAfterKey($fleet,['uuid' => $this->uuid, 'public_id' => $this->public_id], 'id');
        }

        return $fleet;
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
            'service_area' => new ServiceArea($this->serviceArea),
            'updated_at' => $this->updated_at,
            'created_at' => $this->created_at,
        ];
    }
}
