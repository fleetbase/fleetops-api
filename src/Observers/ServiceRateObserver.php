<?php

namespace Fleetbase\FleetOps\Observers;

use Fleetbase\FleetOps\Models\ServiceRate;
use Fleetbase\Support\Utils;

class ServiceRateObserver
{
    /**
     * Handle the ServiceRate "creating" event.
     *
     * @param  \Fleetbase\FleetOps\Models\ServiceRate  $serviceRate
     * @return void
     */
    public function created(ServiceRate $serviceRate)
    {
        // convert these attributes to numbers only
        $toNumbers = ['base_fee', 'per_km_flat_rate_fee', 'peak_hours_flat_fee', 'peak_hours_percent', 'cod_flat_fee', 'cod_percent'];

        // convert to numbers for all attributes above
        foreach ($toNumbers as $attribute) {
            if (isset($serviceRate->{$attribute})) {
                $serviceRate->{$attribute} = Utils::numbersOnly($serviceRate->{$attribute});
            }
        }
    }

    /**
     * Handle the ServiceRate "creating" event.
     *
     * @param  \Fleetbase\FleetOps\Models\ServiceRate  $serviceRate
     * @return void
     */
    public function deleted(ServiceRate $serviceRate)
    {
        $serviceRate->load(['parcelFees', 'rateFees']);

        Utils::deleteModels($serviceRate->parcelFees);
        Utils::deleteModels($serviceRate->rateFees);
    }
}
