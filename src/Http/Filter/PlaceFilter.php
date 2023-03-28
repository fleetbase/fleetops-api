<?php

namespace Fleetbase\Http\Filter;

class PlaceFilter extends Filter
{
    public function queryForInternal()
    {
        $this->builder->where('company_uuid', $this->session->get('company'));
    }

    public function query(string $searchQuery)
    {
        $this->builder->search($searchQuery);
    }
}
