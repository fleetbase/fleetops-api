<?php

namespace Fleetbase\FleetOps\Http\Controllers\Internal\v1;

use Fleetbase\FleetOps\Http\Controllers\FleetOpsController;
use Illuminate\Support\Facades\DB;

class DriverController extends FleetOpsController
{
  /**
   * The resource to query
   *
   * @var string
   */
  public $resource = 'driver';

  /**
   * Get all status options for an driver
   *
   * @return \Illuminate\Http\Response
   */
  public function statuses()
  {
    $statuses = DB::table('drivers')
      ->select('status')
      ->where('company_uuid', session('company'))
      ->distinct()
      ->get()
      ->pluck('status')
      ->filter()
      ->values();

    return response()->json($statuses);
  }
}
