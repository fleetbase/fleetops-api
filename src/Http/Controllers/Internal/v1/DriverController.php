<?php

namespace Fleetbase\FleetOps\Http\Controllers\Internal\v1;

use Fleetbase\FleetOps\Http\Controllers\FleetOpsController;
use Fleetbase\FleetOps\Exports\DriverExport;
use Fleetbase\Http\Requests\ExportRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

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

  /**
   * Export the drivers to excel or csv
   *
   * @param  \Illuminate\Http\Request  $query
   * @return \Illuminate\Http\Response
   */
  public static function export(ExportRequest $request)
  {
    $format = $request->input('format', 'xlsx');
    $fileName = trim(Str::slug('drivers-' . date('Y-m-d-H:i')) . '.' . $format);

    return Excel::download(new DriverExport(), $fileName);
  }
}
