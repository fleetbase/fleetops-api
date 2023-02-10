<?php

namespace Fleetbase\Http\Controllers\Internal\v1;

use Fleetbase\Exports\ContactExport;
use Fleetbase\Http\Controllers\FleetbaseController;
use Fleetbase\Http\Requests\ExportRequest;
use Fleetbase\Models\Contact;
use Fleetbase\Support\Resp;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ContactController extends FleetbaseController
{
    /**
     * The resource to query
     *
     * @var string
     */
    public $resource = 'contact';
    
    /**
     * Returns the contact as a `facilitator-contact`
     *
     * @var string id
     */
    public function getAsFacilitator($id)
    {
        $contact = Contact::where('uuid', $id)->first();

        if (!$contact) {
            return response()->error('Facilitator not found.');
        }

        return response()->json([
            'facilitatorContact' => $contact,
        ]);
    }

    /**
     * Returns the contact as a `customer-contact`
     *
     * @var string id
     */
    public function getAsCustomer($id)
    {
        $contact = Contact::where('uuid', $id)->first();

        if (!$contact) {
            return response()->error('Customer not found.');
        }

        return response()->json([
            'customerContact' => $contact,
        ]);
    }

    /**
     * Export the contacts to excel or csv
     *
     * @param  \Illuminate\Http\Request  $query
     * @return \Illuminate\Http\Response
     */
    public static function export(ExportRequest $request)
    {
        $format = $request->input('format', 'xlsx');
        $fileName = trim(Str::slug('contacts-' . date('Y-m-d-H:i')) . '.' . $format);

        return Excel::download(new ContactExport(), $fileName);
    }
}
