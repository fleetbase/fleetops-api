<?php

namespace Fleetbase\FleetOps\Http\Controllers\Api\v1;

use Illuminate\Http\Request;
use Fleetbase\Http\Controllers\Controller;
use Fleetbase\FleetOps\Http\Requests\CreateVehicleRequest;
use Fleetbase\FleetOps\Http\Requests\UpdateVehicleRequest;
use Fleetbase\FleetOps\Http\Resources\v1\VehicleDevice as VehicleDeviceResource;
use Fleetbase\FleetOps\Models\Vehicle;
use Fleetbase\FleetOps\Models\Driver;
use Fleetbase\FleetOps\Models\VehicleDevice;
use Fleetbase\FleetOps\Support\Utils;

class VehicleDeviceController extends Controller
{
    /**
     * Creates a new Fleetbase Vehicle resource.
     *
     * @param  \Fleetbase\Http\Requests\CreateVehicleRequest  $request
     * @return \Fleetbase\Http\Resources\Vehicle
     */
    public function create(CreateVehicleRequest $request)
    {

        echo "demo";
        // get request input
        $input = $request->only(['vehicle_uuid', 'device_id', 'device_provider', 'device_type', 'device_name', 'device_model', 'manufacturer', 'serial_number', 'installation_date', 'last_maintenance_date', 'meta', 'status', 'data_frequency', 'notes']);

        // create instance of vehicle model
        $vehicleDevice = new VehicleDevice();

        // apply user input to vehicle
        $vehicleDevice = $vehicleDevice->fill($input);

        // save the vehicle
        $vehicleDevice->save();

        // response the driver resource
        return new VehicleDeviceResource($vehicleDevice);
    }
}
