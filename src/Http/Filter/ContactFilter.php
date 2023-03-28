<?php

namespace Fleetbase\Http\Filter;

class ContactFilter extends Filter
{
    public function queryForInternal()
    {
        $this->builder->where('company_uuid', $this->session->get('company'));
    }

    /**
     * @todo Migrate to Storefrony API package
     *
     * @param string $storefront
     * @return void
     */
    public function storefront(string $storefront)
    {
        $this->builder->whereHas(
            'customerOrders',
            function ($query) use ($storefront) {
                $query->where('meta->storefront_id', $storefront);
            }
        );
    }
}
