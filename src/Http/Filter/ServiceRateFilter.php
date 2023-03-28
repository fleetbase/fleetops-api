<?php

namespace Fleetbase\Http\Filter;

class ServiceRateFilter extends Filter
{
    public function queryForInternal()
    {
        $this->builder->where('company_uuid', $this->session->get('company'));
    }
}
