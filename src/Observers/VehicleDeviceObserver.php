<?php

namespace Fleetbase\FleetOps\Observers;

use Fleetbase\FleetOps\Models\VehicleDevice;
use Fleetbase\Flespi\Support\FlespiIntegration;

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
    if ($vehicleDevice->device_provider === 'flespi') {
      FlespiIntegration::createOrAssignDeviceToStream($vehicleDevice->device_id);
    }
  }
}
