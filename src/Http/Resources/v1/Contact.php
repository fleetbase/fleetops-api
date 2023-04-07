<?php

namespace Fleetbase\FleetOps\Http\Resources\v1;

use Fleetbase\Http\Resources\FleetbaseResource;
use Fleetbase\Support\Http;
use Illuminate\Support\Arr;

class Contact extends FleetbaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $contact = [
            'id' => $this->public_id,
            'internal_id' => $this->internal_id,
            'name' => $this->name,
            'title' => $this->title ?? null,
            'email' => $this->email ?? null,
            'phone' => $this->phone ?? null,
            'photo_url' => $this->photo_url ?? null,
            'type' => $this->type ?? null,
            'meta' => $this->meta ?? [],
            'slug' => $this->slug ?? null,
            'updated_at' => $this->updated_at,
            'created_at' => $this->created_at,
        ];

        if (Http::isInternalRequest()) {
            $contact = Arr::insertAfterKey($contact,['uuid' => $this->uuid, 'public_id' => $this->public_id], 'id');
        }

        return $contact;
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
            'internal_id' => $this->internal_id,
            'name' => $this->name,
            'title' => $this->title ?? null,
            'email' => $this->email ?? null,
            'phone' => $this->phone ?? null,
            'photo_url' => $this->photo_url ?? null,
            'type' => $this->type ?? null,
            'meta' => $this->meta ?? [],
            'slug' => $this->slug ?? null,
            'updated_at' => $this->updated_at,
            'created_at' => $this->created_at,
        ];
    }
}
