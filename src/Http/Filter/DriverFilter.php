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

    public function query(?string $searchQuery)
    {
        $this->builder->where(function ($query) use ($searchQuery) {
            $query->orWhereHas(
                'user',
                function ($query) use ($searchQuery) {
                    $query->searchWhere(['name', 'email', 'phone'], $searchQuery);
                }
            );

            $query->orWhere(
                function ($query) use ($searchQuery) {
                    $query->searchWhere(['drivers_license_number'], $searchQuery);
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

    public function facilitator(string $facilitator)
    {
        $this->builder->where('vendor_uuid', $facilitator);
    }

    public function vehicle(string $vehicle)
    {
        $this->builder->whereHas(
            'vehicle', 
            function ($query) use ($vehicle) {
                $query->search($vehicle);
            }
        );
    }

    public function fleet(string $fleet)
    {
        $this->builder->where('fleets', $fleet);
    }

    public function driversLicenseNumber(?string $driversLicenseNumber)
    {
        $this->builder->searchWhere('drivers_license_number', $driversLicenseNumber);
    }

    public function phone(string $phone)
    {
        $this->builder->whereHas(
            'phone', 
            function ($query) use ($phone) {
                $query->search($phone);
            }
        );
    }

    public function country(?string $country)
    {
        $this->builder->searchWhere('country', $country);
    }

    public function status(?string $status)
    {
        $this->builder->searchWhere('status', $status);
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
