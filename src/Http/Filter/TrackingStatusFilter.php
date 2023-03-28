<?php

namespace Fleetbase\Http\Filter;

class TrackingStatusFilter extends Filter
{
    public function queryForInternal()
    {
        $this->builder->where('company_uuid', $this->session->get('company'));
    }

    public function order(string $order)
    {
        $this->builder->whereHas(
            'trackingNumber',
            function ($query) use ($order) {
                $query->whereHas(
                    'order',
                    function ($query) use ($order) {
                        $query->where('public_id', $order);
                    }
                );
            }
        );
    }

    public function entity(string $entity)
    {
        $this->builder->whereHas(
            'trackingNumber',
            function ($query) use ($entity) {
                $query->whereHas(
                    'entity',
                    function ($query) use ($entity) {
                        $query->where('public_id', $entity);
                    }
                );
            }
        );
    }
}
