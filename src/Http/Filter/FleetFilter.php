<?php

namespace Fleetbase\FleetOps\Http\Filter;

use Fleetbase\Http\Filter\Filter;

class FleetFilter extends Filter
{
    public function queryForInternal()
    {
        $this->builder->where('company_uuid', $this->session->get('company'))->with(['serviceArea', 'zone']);
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

    public function serviceAreaName(?string $serviceAreaName)
    {
        $this->builder->whereHas(
            'serviceArea', 
            function ($query) use ($serviceAreaName) {
                $query->searchWhere('name', $serviceAreaName);
            }
        );
    }

    public function zoneName(?string $zoneName)
    {
        $this->builder->whereHas(
            'zone', 
            function ($query) use ($serviceAreaName) {
                $query->searchWhere('name', $zoneName);
            }
        );
    }

    public function internalId(?string $internalId)
    {
        $this->builder->searchWhere('internal_id', $internalId);
    }

    public function publicId(?string $publicId)
    {
        $this->builder->searchWhere('public_id', $publicId);
    }

    public function task(?string $task)
    {
        $this->builder->searchWhere('task', $task);
    }

    public function status(?string $status)
    {
        $this->builder->searchWhere('status', $status);
    }
}
