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

    public function unassigned(bool $unassigned)
    {
        if ($unassigned) {
            $this->builder->where(
                function ($q) {
                    $q->whereNull('driver_assigned_uuid');
                    $q->whereNotIn('status', ['completed', 'canceled']);
                }
            );
        }
    }

    public function tracking(string $tracking)
    {
        $this->builder->whereHas(
            'trackingNumber',
            function ($query) use ($tracking) {
                $query->where('tracking_number', $tracking);
            }
        );
    }

    public function active(bool $active = false)
    {
        if ($active) {
            $this->builder->where(
                function ($q) {
                    $q->whereNotIn('status', ['created', 'dispatched', 'pending', 'canceled', 'completed']);
                    $q->whereNotNull('driver_assigned_uuid');
                }
            );
        }
    }

    /**
     * @todo Migrate to Storefront package via Expansion
     *
     * @param string $storefront
     * @return void
     */
    public function storefront(string $storefront)
    {
        $this->builder->where('meta->storefront_id', $storefront);
    }

    public function customer(string $customer)
    {
        $this->builder->where('customer_uuid', $customer);
    }

    public function facilitator(string $facilitator)
    {
        $this->builder->where('facilitator_uuid', $facilitator);
    }

    public function payload(string $payload)
    {
        $this->builder->whereHas(
            'payload',
            function ($query) use ($payload) {
                $query->where('public_id', $payload);
            }
        );
    }

    public function pickup(string $pickup)
    {
        $this->builder->whereHas(
            'payload',
            function ($query) use ($pickup) {
                $query->where('pickup_uuid', $pickup);
            }
        );
    }

    public function dropoff(string $dropoff)
    {
        $this->builder->whereHas(
            'payload',
            function ($query) use ($dropoff) {
                $query->where('dropoff_uuid', $dropoff);
            }
        );
    }

    public function return(string $return)
    {
        $this->builder->whereHas(
            'payload',
            function ($query) use ($return) {
                $query->where('return_uuid', $return);
            }
        );
    }

    public function driver(string $driver)
    {
        $this->builder->where('driver_assigned_uuid', $driver);
    }

    public function sort(string $sort)
    {
        list($param, $direction) = Http::useSort($sort);

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
