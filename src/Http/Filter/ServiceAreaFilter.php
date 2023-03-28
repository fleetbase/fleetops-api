<?php

namespace Fleetbase\Http\Filter;

class ServiceAreaFilter extends Filter
{
    public function queryForInternal()
    {
        $this->builder->where('company_uuid', $this->session->get('company'));
    }
}
