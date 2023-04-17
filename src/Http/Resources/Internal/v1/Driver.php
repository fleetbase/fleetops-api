<?php

namespace Fleetbase\FleetOps\Http\Resources\Internal\v1;

use Fleetbase\FleetOps\Http\Resources\v1\Driver as DriverResource;
use Illuminate\Support\Arr;

class Driver extends DriverResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $driver = parent::toArray($request);
        $driver = Arr::insertAfterKey($driver, ['uuid' => $this->uuid, 'public_id' => $this->public_id], 'id');
        $driver['vehicle'] = new Vehicle($this->whenLoaded('vehicle'));
        $driver['current_job'] = new CurrentJob($this->whenLoaded('currentJob'));
        $driver['fleets'] = Fleet::collection($this->whenLoaded('fleets'));

        return $driver;
    }
}
