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

        if ($this->vehicle) {
            $driver['vehicle'] = new Vehicle($this->vehicle);
        }

        if ($this->currentJob) {
            $driver['current_job'] = new CurrentJob($this->currentJob);
        }

        $driver['fleets'] = $this->fleets->mapInto(Fleet::class);

        return $driver;
    }
}
