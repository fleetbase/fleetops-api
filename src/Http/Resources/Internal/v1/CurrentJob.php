<?php

namespace Fleetbase\Http\Resources\Internal\v1;

use Fleetbase\Http\Resources\FleetbaseResource;

class CurrentJob extends FleetbaseResource
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
            'internal_id' => $this->internal_id,
            'type' => $this->type,
            'status' => $this->status,
            'meta' => $this->meta ?? [],
            'updated_at' => $this->updated_at,
            'created_at' => $this->created_at,
        ];
    }
}