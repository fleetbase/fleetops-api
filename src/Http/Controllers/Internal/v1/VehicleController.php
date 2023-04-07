<?php

namespace Fleetbase\FleetOps\Http\Controllers\Internal\v1;

use Fleetbase\FleetOps\Http\Controllers\FleetOpsController;

use Fleetbase\Exports\VehicleExport;
use Fleetbase\Http\Controllers\FleetbaseController;
use Fleetbase\Http\Requests\ExportRequest;
use Fleetbase\FleetOps\Models\Driver;
use Fleetbase\FleetOps\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class VehicleController extends FleetOpsController
{
    /**
     * The resource to query
     *
     * @var string
     */
    public $resource = 'vehicle';

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
            function (&$request, &$vehicle) {
                $driverId = $request->or(['driver_uuid', 'vehicle.driver_uuid', 'vehicle.driver.uuid']);

                if ($driverId) {
                    $driver = Driver::where('uuid', $driverId)->whereNull('deleted_at')->withoutGlobalScopes()->first();

                    if ($driver) {
                        // assign this vehicle to driver
                        $driver->assignVehicle($vehicle);
                        // set driver to vehicle
                        $vehicle->setRelation('driver', $driver);
                    }
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
            null,
            function (&$request, &$vehicle) {
                $driverId = $request->or(['driver_uuid', 'vehicle.driver_uuid', 'vehicle.driver.uuid']);

                if ($driverId) {
                    $driver = Driver::where('uuid', $driverId)->whereNull('deleted_at')->withoutGlobalScopes()->first();

                    if ($driver) {
                        // assign this vehicle to driver
                        $driver->assignVehicle($vehicle);
                        // set driver to vehicle
                        $vehicle->setRelation('driver', $driver);
                    }
                }
            }
        );
    }

    /**
     * Get all status options for an vehicle
     *
     * @return \Illuminate\Http\Response
     */
    public function statuses()
    {
        $statuses = DB::table('vehicles')
            ->select('status')
            ->where('company_uuid', session('company'))
            ->distinct()
            ->get()
            ->pluck('status')
            ->filter()
            ->values();

        return response()->json($statuses);
    }

    /**
     * Get all avatar options for an vehicle
     *
     * @return \Illuminate\Http\Response
     */
    public function avatars()
    {
        $options = Vehicle::getAvatarOptions();

        return response()->json($options);
    }

    /**
     * Export the vehicles to excel or csv
     *
     * @param  \Illuminate\Http\Request  $query
     * @return \Illuminate\Http\Response
     */
    public static function export(ExportRequest $request)
    {
        $format = $request->input('format', 'xlsx');
        $fileName = trim(Str::slug('vehicles-' . date('Y-m-d-H:i')) . '.' . $format);

        return Excel::download(new VehicleExport(), $fileName);
    }
}
