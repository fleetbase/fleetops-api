<?php

namespace Fleetbase\FleetOps\Http\Resources\v1;

use Fleetbase\Http\Resources\FleetbaseResource;

class ServiceQuote extends FleetbaseResource
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
            'service_rate' => $this->serviceRate->public_id ?? null,
            'facilitator' => $this->integratedVendor->public_id ?? null,
            'request_id' => $this->request_id,
            'amount' => $this->amount,
            'currency' => $this->currency,
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
            'service_rate' => $this->serviceRate->public_id ?? null,
            'facilitator' => $this->integratedVendor->public_id ?? null,
            'request_id' => $this->request_id,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'updated_at' => $this->updated_at,
            'created_at' => $this->created_at,
        ];
    }
}
