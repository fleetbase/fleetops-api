<?php

namespace Fleetbase\Http\Filter;

class FleetFilter extends Filter
{
    public function queryForInternal()
    {
        $this->builder->where('company_uuid', $this->session->get('company'))->with(['serviceArea', 'zone']);
    }
}
