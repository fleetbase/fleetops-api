<?php

namespace Fleetbase\Http\Controllers\Internal\v1;

use Fleetbase\Http\Controllers\FleetbaseController;
use Fleetbase\Support\Geo;
use Illuminate\Http\Request;

class ServiceAreaController extends FleetbaseController
{
    /**
     * The resource to query
     *
     * @var string
     */
    public $resource = 'service_area';

    /**
     * Creates a record with request payload
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function createRecord(Request $request)
    {
        return $this->model::createRecordFromRequest($request, function (&$request, &$input) {
            // $input['border'] = Geo::polygonFor($input['country']);
        });
    }
}
