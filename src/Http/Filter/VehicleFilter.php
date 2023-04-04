<?php

namespace Fleetbase\Http\Filter;

class VehicleFilter extends Filter
{
    public function queryForInternal()
    {
        $this->builder->where('company_uuid', $this->session->get('company'));
    }

    public function query(?string $query)
    {
        $this->builder->search($query);
    }

    public function vin(?string $vin)
    {
        $this->builder->searchWhere('vin', $vin);
    }

    public function internalId(?string $internalId)
    {
        $this->builder->searchWhere('internal_id', $internalId);
    }

    public function plateNumber(?string $plateNumber)
    {
        $this->builder->searchWhere('plate_number', $plateNumber);
    }

    public function vehicleMake(?string $make)
    {
        $this->builder->searchWhere('make', $make);
    }

    public function vehicleModel(?string $model)
    {
        $this->builder->searchWhere('model', $model);
    }
}
