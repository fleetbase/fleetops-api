<?php

namespace Fleetbase\Http\Resources\Internal\v1;

use Fleetbase\Http\Resources\v1\TrackingNumber as TrackingNumberResource;
use Illuminate\Support\Arr;

class TrackingNumber extends TrackingNumberResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $trackingNumber = parent::toArray($request);
        $trackingNumber = Arr::insertAfterKey($trackingNumber, ['uuid' => $this->uuid, 'public_id' => $this->public_id], 'id');

        return $trackingNumber;
    }
}
