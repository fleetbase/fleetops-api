<?php

namespace Fleetbase\Http\Resources\Internal\v1;

use Fleetbase\Http\Resources\v1\PurchaseRate as PurchaseRateResource;
use Illuminate\Support\Arr;

class PurchaseRate extends PurchaseRateResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $purchaseRate = parent::toArray($request);
        $purchaseRate = Arr::insertAfterKey($purchaseRate, ['uuid' => $this->uuid], 'id');

        return $purchaseRate;
    }
}
