<?php

namespace Fleetbase\Http\Controllers\Internal\v1;

use Fleetbase\Http\Controllers\FleetbaseController;
use Illuminate\Support\Facades\DB;

class DriverController extends FleetbaseController
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
      ->filter()
      ->pluck('status');

    return response()->json($statuses);
  }
}
