<?php

namespace Fleetbase\FleetOps\Http\Controllers\Internal\v1;

use Fleetbase\FleetOps\Http\Controllers\FleetOpsController;

use Fleetbase\Http\Controllers\FleetbaseController;
use Illuminate\Http\Request;

class PayloadController extends FleetOpsController
{
    /**
     * The resource to query
     *
     * @var string
     */
    public $resource = 'payload';

    /**
     * Updates a record by an identifier with request payload
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updateRecord(Request $request, string $id)
    {
        return $this->model::updateRecordFromRequest(
            $request,
            function (&$request, &$payload, &$input) {
                $payload->updateWaypoints($input['waypoints'] ?? []);
                $payload->flushOrderCache();
            }
        );
    }
}
