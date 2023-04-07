<?php

namespace Fleetbase\FleetOps\Http\Filter;

use Fleetbase\Http\Filter\Filter;

class VendorFilter extends Filter
{
    public function query(?string $searchQuery)
    {
        $this->builder->where(function ($query) use ($searchQuery) {
            $query->orWhereHas(
                'user',
                function ($query) use ($searchQuery) {
                    $query->searchWhere(['name', 'email', 'phone', 'address'], $searchQuery);
                }
            );
        });
    }

    public function internalId(?string $internalId)
    {
        $this->builder->searchWhere('internal_id', $internalId);
    }

    public function publicId(?string $publicId)
    {
        $this->builder->searchWhere('public_id', $publicId);
    }

    public function type(?string $type)
    {
        $this->builder->searchWhere('type', $type);
    }

    public function country(?string $country)
    {
        $this->builder->searchWhere('country', $country);
    }

    public function status(?string $status)
    {
        $this->builder->searchWhere('status', $status);
    }
}
