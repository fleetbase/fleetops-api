<?php

namespace Fleetbase\Http\Filter;

class PlaceFilter extends Filter
{
    public function query(?string $searchQuery)
    {
        $this->builder->where(function ($query) use ($searchQuery) {
            $query->orWhereHas(
                'place',
                function ($query) use ($searchQuery) {
                    $query->searchWhere(['name','email','phone','address',], $searchQuery);
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
    
    public function country(?string $country)
    {
        $this->builder->searchWhere('country_name', $country);
    }
}
