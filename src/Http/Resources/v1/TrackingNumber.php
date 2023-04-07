<?php

namespace Fleetbase\FleetOps\Http\Resources\v1;

use Fleetbase\Support\Utils;
use Fleetbase\Http\Resources\FleetbaseResource;

class TrackingNumber extends FleetbaseResource
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
            'tracking_number' => $this->tracking_number,
            'subject' => Utils::get($this->owner, 'public_id'),
            'region' => $this->region,
            'status' => $this->last_status,
            'status_code' => $this->last_status_code,
            'qr_code' => $this->qr_code,
            'barcode' => $this->barcode,
            'type' => Utils::getTypeFromClassName($this->owner_type),
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
            'tracking_number' => $this->tracking_number,
            'subject' => Utils::get($this->owner, 'public_id'),
            'region' => $this->region,
            'qr_code' => $this->qr_code,
            'barcode' => $this->barcode,
            'type' => Utils::getTypeFromClassName($this->owner_type),
            'updated_at' => $this->updated_at,
            'created_at' => $this->created_at,
        ];
    }
}
