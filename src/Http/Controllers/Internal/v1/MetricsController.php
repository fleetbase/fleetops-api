<?php

namespace Fleetbase\FleetOps\Http\Controllers\Internal\v1;

use Fleetbase\FleetOps\Http\Controllers\FleetOpsController;

use Fleetbase\FleetOps\Support\Metrics;
use Fleetbase\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Exception;

class MetricsController extends Controller {
    public function all(Request $request)
    {
        $start = $request->date('start');
        $end = $request->date('end');
        $discover = $request->array('discover', []);

        try {
            $data = Metrics::forCompany($request->user()->company, $start, $end)->with($discover)->get();
        } catch (Exception $e) {
            return response()->error($e->getMessage());
        }

        return response()->json($data);
    }
}