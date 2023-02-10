<?php

namespace Fleetbase\Http\Filter;

use Fleetbase\Support\Http;

class OrderFilter extends Filter
{
    public function queryForInternal()
    {
        $this->builder
            ->whereHas(
                'payload',
                function ($q) {
                    $q->where(
                        function ($q) {
                            $q->whereHas('waypoints');
                            $q->orWhereHas('pickup');
                            $q->orWhereHas('dropoff');
                        }
                    );
                    $q->with(['entities', 'waypoints', 'dropoff', 'pickup', 'return']);
                }
            )
            ->whereHas('trackingNumber')
            ->whereHas('trackingStatuses')
            ->with(
                [
                    'payload',
                    'trackingNumber',
                    'trackingStatuses'
                ]
            );
    }

    public function unassigned()
    {
        if ($this->request->boolean('unassigned')) {
            $this->builder->where(function ($q) {
                $q->whereNull('driver_assigned_uuid');
                $q->whereNotIn('status', ['completed', 'canceled']);
            });
        }
    }

    public function tracking($value)
    {
        $this->builder->whereHas('trackingNumber', function ($trackingNumberQuery) {
            $trackingNumberQuery->stringWhere('tracking_number', $this->request->input('tracking'));
        });
    }

    public function sort($value)
    {
        list($param, $direction) = Http::useSort($value);

        switch ($param) {
            case 'tracking':
            case 'tracking_number':
                $this->builder->addSelect(['tns.tracking_number as tracking']);
                $this->builder->join('tracking_numbers as tns', 'tns.uuid', '=', 'orders.tracking_number_uuid')->orderBy('tracking', $direction);
                break;

            case 'customer':
                $this->builder->select(['orders.*', 'vendors.name as customer_name']);
                $this->builder->join('contacts', 'contacts.uuid', '=', 'orders.customer_uuid')->orderBy('customer_name', $direction);
                break;

            case 'facilitator':
                $this->builder->select(['orders.*', 'vendors.name as facilitator_name']);
                $this->builder->join('vendors', 'vendors.uuid', '=', 'orders.facilitator_uuid')->orderBy('facilitator_name', $direction);
                break;

            case 'pickup':
                $this->builder->select(['orders.*', 'places.name as pickup_name']);
                $this->builder->join('payloads', 'payloads.uuid', '=', 'orders.payload_uuid');
                $this->builder->join('places', 'places.uuid', '=', 'payloads.pickup_uuid')->orderBy('pickup_name', $direction);
                break;

            case 'dropoff':
                $this->builder->select(['orders.*', 'places.name as dropoff_name']);
                $this->builder->join('payloads', 'payloads.uuid', '=', 'orders.payload_uuid');
                $this->builder->join('places', 'places.uuid', '=', 'payloads.dropoff_uuid')->orderBy('dropoff_name', $direction);
                break;
        }

        return $this->builder;
    }
}
