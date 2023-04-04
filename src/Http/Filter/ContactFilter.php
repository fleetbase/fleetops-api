<?php

namespace Fleetbase\Http\Filter;

class ContactFilter extends Filter
{
    public function queryForInternal()
    {
        $this->builder->where(
            function ($query) {
                $query->where('company_uuid', $this->session->get('company'));
                $query->orWhereHas(
                    'user',
                    function ($query) {
                        $query->where('company_uuid', $this->session->get('company'));
                    }
                );
            }
        );
    }

    public function query(?string $searchQuery)
    {
        $this->builder->where(function ($query) use ($searchQuery) {
            $query->orWhereHas(
                'user',
                function ($query) use ($searchQuery) {
                    $query->searchWhere(['name', 'email', 'phone'], $searchQuery);
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

    public function publicIdType(string $publicIdType)
    {
        $this->builder->whereHas(
            'publicIdType', 
            function ($query) use ($publicIdType) {
                $query->search($publicIdType);
            }
        );
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
