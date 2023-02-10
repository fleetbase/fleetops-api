<?php

namespace Fleetbase\Http\Controllers\Internal\v1;

use Fleetbase\Exports\VendorExport;
use Fleetbase\Http\Controllers\FleetbaseController;
use Fleetbase\Http\Requests\BulkActionRequest;
use Fleetbase\Http\Requests\ExportRequest;
use Fleetbase\Models\Vendor;
use Fleetbase\Support\Resp;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class VendorController extends FleetbaseController
{
    /**
     * The resource to query
     *
     * @var string
     */
    public $resource = 'vendor';

    /**
     * Returns the vendor as a `facilitator-vendor`
     *
     * @var string id
     */
    public function getAsFacilitator($id)
    {
        $vendor = Vendor::where('uuid', $id)->first();

        if (!$vendor) {
            return response()->error('Facilitator not found.');
        }

        return response()->json([
            'facilitatorVendor' => $vendor,
        ]);
    }

    /**
     * Returns the vendor as a `customer-vendor`
     *
     * @var string id
     */
    public function getAsCustomer($id)
    {
        $vendor = Vendor::where('uuid', $id)->first();

        if (!$vendor) {
            return response()->error('Customer not found.');
        }

        return response()->json([
            'customerVendor' => $vendor,
        ]);
    }

    /**
     * Export the vendors to excel or csv
     *
     * @param  \Illuminate\Http\Request  $query
     * @return \Illuminate\Http\Response
     */
    public static function export(ExportRequest $request)
    {
        $format = $request->input('format', 'xlsx');
        $fileName = trim(Str::slug('vendors-' . date('Y-m-d-H:i')) . '.' . $format);

        return Excel::download(new VendorExport(), $fileName);
    }

    /**
     * Bulk delete resources.
     *
     * @param  Fleetbase\Http\Requests\BulkActionRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function bulkDelete(BulkActionRequest $request)
    {
        $ids = $request->input('ids', []);

        if (!$ids) {
            return response()->error('Nothing to delete.');
        }

        /** @var \Fleetbase\Models\Vendor */
        $count = Vendor::whereIn('uuid', $ids)->count();
        $deleted = Vendor::whereIn('uuid', $ids)->delete();

        if (!$deleted) {
            return response()->error('Failed to bulk delete vendors.');
        }

        return response()->json(
            [
                'status' => 'OK',
                'message' => 'Deleted ' . $count . ' vendors',
            ],
            200
        );
    }
}
