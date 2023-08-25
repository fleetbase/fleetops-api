<?php

namespace Fleetbase\FleetOps\Http\Controllers\Internal\v1;

use Fleetbase\FleetOps\Http\Filter\PlaceFilter;
use Fleetbase\Http\Controllers\Controller;
use Fleetbase\FleetOps\Http\Resources\v1\Order as OrderResource;
use Fleetbase\FleetOps\Models\Order;
use Fleetbase\FleetOps\Models\Driver;
use Fleetbase\FleetOps\Models\Place;
use Fleetbase\FleetOps\Models\Route;
use Illuminate\Http\Request;

class LiveController extends Controller
{
    public function coordinates()
    {
        $coordinates = [];

        $orders = Order::where('company_uuid', session('company'))
            ->whereNotIn('status', ['canceled', 'completed'])
            ->get();

        foreach ($orders as $order) {
            $coordinates[] = $order->getCurrentDestinationLocation();
        }

        return response()->json($coordinates);
    }

    public function routes()
    {
        $routes = Route::where('company_uuid', session('company'))
            ->whereHas(
                'order',
                function ($q) {
                    $q->whereNotIn('status', ['canceled', 'completed']);
                    $q->whereNotNull('driver_assigned_uuid');
                    $q->whereNull('deleted_at');
                }
            )
            ->get();

        return response()->json($routes);
    }

    public function orders()
    {
        $orders = Order::where('company_uuid', session('company'))
            ->whereHas('payload')
            ->whereNotIn('status', ['canceled', 'completed'])
            ->whereNotNull('driver_assigned_uuid')
            ->whereNull('deleted_at')
            ->get();

        return OrderResource::collection($orders);
    }

    public function drivers()
    {
        $drivers = Driver::where(['company_uuid' => session('company'), 'online' => 1])
            ->whereHas(
                'currentJob',
                function ($q) {
                    $q->whereNotIn('status', ['canceled', 'completed']);
                }
            )
            ->get();

        return response()->json($drivers);
    }

    public function places(Request $request)
    {
        // query places
        $places = Place::where(['company_uuid' => session('company')])
            ->filter(new PlaceFilter($request))
            ->get();

        return response()->json($places);
    }
}
