<?php

namespace Fleetbase\FleetOps\Observers;

use Fleetbase\FleetOps\Models\Driver;
use Fleetbase\Models\User;

class DriverObserver
{
    /**
     * Handle the Driver "deleted" event.
     *
     * @param  \Fleetbase\FleetOps\Models\Driver  $driver
     * @return void
     */
    public function deleted(Driver $driver)
    {
        // if the driver is deleted, delete their user account assosciated as well
        User::where('uuid', $driver->user_uuid)->delete();
    }
}
