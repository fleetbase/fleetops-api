<?php

namespace Fleetbase\FleetOps\Observers;

use Fleetbase\FleetOps\Models\VehicleDevice;

class VehicleDeviceObserver
{
  /**
   * Handle the Vehicle "created" event.
   *
   * @param  \Fleetbase\FleetOps\Models\VehicleDevice  $vehicleDevice
   * @return void
   */
  public function created(VehicleDevice $vehicleDevice)
  {
    $data = [
      "device_id" => $vehicleDevice->device_id,
    ];
    $vehicleDevice->createStream($data);
  }
}
