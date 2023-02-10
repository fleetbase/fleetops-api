<?php

namespace Fleetbase\Http\Resources\v1;

use Fleetbase\Http\Resources\FleetbaseResource;
use Fleetbase\Support\Http;
use Fleetbase\Support\Utils;
use Illuminate\Support\Arr;

class DeletedResource extends FleetbaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $deleted = [
            'id' => $this->public_id,
            'object' => $this->getObjectType(),
            'time' => $this->deleted_at,
            'deleted' => true,
        ];

        if (Http::isInternalRequest()) {
            $deleted = Arr::insertAfterKey($deleted, ['uuid' => $this->uuid, 'public_id' => $this->public_id], 'id');
        }

        return $deleted;
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
            'object' => $this->getObjectType(),
            'time' => $this->deleted_at,
            'deleted' => true,
        ];
    }

    /**
     * Get the object type for this resource.
     *
     * @return string
     */
    public function getObjectType()
    {
        return strtolower(Utils::getClassName($this->resource));
    }
}
