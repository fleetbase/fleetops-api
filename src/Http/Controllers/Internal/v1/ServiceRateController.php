<?php

namespace Fleetbase\Http\Controllers\Internal\v1;

use Fleetbase\Http\Controllers\FleetbaseController;
use Fleetbase\Http\Requests\BulkActionRequest;
use Fleetbase\Models\ServiceRate;
use Illuminate\Http\Request;
use Brick\Geo\Point;

class ServiceRateController extends FleetbaseController
{
    /**
     * The resource to query
     *
     * @var string
     */
    public $resource = 'service_rate';

    /**
     * Creates a record with request payload
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function createRecord(Request $request)
    {
        return $this->model::createRecordFromRequest(
            $request,
            null,
            function (&$request, &$serviceRate) {
                $serviceRateFees = $request->input('serviceRate.rate_fees');
                $serviceRateParcelFees = $request->input('serviceRate.parcel_fees');

                if ($serviceRate->isFixedMeter() || $serviceRate->isPerDrop()) {
                    $serviceRate->setServiceRateFees($serviceRateFees);
                }

                if ($serviceRate->isParcelService()) {
                    $serviceRate->setServiceRateParcelFees($serviceRateParcelFees);
                }
            }
        );
    }

    /**
     * Updates a record with request payload
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updateRecord(Request $request, string $id)
    {
        return $this->model::updateRecordFromRequest(
            $request,
            function (&$request, &$serviceRate) {
                $serviceRateFees = $request->input('serviceRate.rate_fees');
                $serviceRateParcelFees = $request->input('serviceRate.parcel_fees');

                if ($serviceRate->isFixedMeter() || $serviceRate->isPerDrop()) {
                    $serviceRate->setServiceRateFees($serviceRateFees);
                }

                if ($serviceRate->isParcelService()) {
                    $serviceRate->setServiceRateParcelFees($serviceRateParcelFees);
                }
            }
        );
    }

    /**
     * Creates a record with request payload
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getServicesForRoute(Request $request)
    {
        $coordinates = explode(';', $request->input('coordinates')); // ex. 1.3621663,103.8845049;1.353151,103.86458
        // convert coordinates to points
        $waypoints = collect($coordinates)->map(
            function ($coord) {
                $coord = explode(',', $coord);
                [$latitude, $longitude] = $coord;

                return Point::fromText("POINT($longitude $latitude)", 4326);
            }
        );

        $applicableServiceRates = ServiceRate::getServicableForWaypoints($waypoints);

        return response()->json($applicableServiceRates);
    }

    /**
     * Updates a order to canceled and updates order activity.
     *
     * @param  \Illuminate\Http\BulkActionRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function bulkDelete(BulkActionRequest $request)
    {
        $count = ServiceRate::whereIn('uuid', $request->input('ids'))->count();
        $deleted = ServiceRate::whereIn('uuid', $request->input('ids'))->delete();

        if (!$deleted) {
            return response()->error('Failed to bulk delete service rates.');
        }

        return response()->json(
            [
                'status' => 'OK',
                'message' => 'Deleted ' . $count . ' service rates',
            ],
            200
        );
    }
}
