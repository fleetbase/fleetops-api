<?php

namespace Fleetbase\Http\Filter;

class DriverFilter extends Filter
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

    public function query(string $searchQuery)
    {
        $this->builder->where(function ($query) use ($searchQuery) {
            $query->orWhereHas(
                'user',
                function ($userQuery) use ($searchQuery) {
                    $userQuery->searchWhere(['name', 'email', 'phone'], $searchQuery);
                }
            );

            $query->orWhere(
                function ($driverQuery) use ($searchQuery) {
                    $driverQuery->searchWhere(['drivers_license_number'], $searchQuery);
                }
            );
        });
    }

    public function facilitator(string $facilitator)
    {
        $this->builder->where('vendor_uuid', $facilitator);
    }

    public function vendor(string $vendor)
    {
        $this->facilitator($vendor);
    }

    public function fleet(string $fleet)
    {
        $this->builder->whereHas(
            'fleets',
            function ($q) use ($fleet) {
                $q->where('fleet_uuid', $fleet);
            }
        );
    }
}
