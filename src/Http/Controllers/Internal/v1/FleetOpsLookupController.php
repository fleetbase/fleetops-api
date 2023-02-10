<?php

namespace Fleetbase\Http\Controllers\Internal\v1;

use Fleetbase\Http\Controllers\Controller;
use Fleetbase\Models\Contact;
use Fleetbase\Models\IntegratedVendor;
use Fleetbase\Models\Vendor;
use Fleetbase\Support\Utils;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;

class FleetOpsLookupController extends Controller
{
    public function polymorphs(Request $request)
    {
        $query = $request->or(['query', 'q']);
        $limit = $request->input('limit', 16);
        $type = Str::lower(Arr::last($request->segments()));
        $resourceType = Str::lower(Str::singular($type));

        $contacts = Contact::where('name', 'like', '%' . $query . '%')
            ->where('company_uuid', session('company'))
            ->limit($limit)
            ->get();

        $vendors = Vendor::where('name', 'like', '%' . $query . '%')
            ->where('company_uuid', session('company'))
            ->limit($limit)
            ->get();

        $results = collect([...$contacts, ...$vendors])
            ->sortBy('name')
            ->map(
                function ($resource) use ($type, $resourceType) {
                    $resource->setAttribute(Str::singular($type) . '_type', Str::lower(Utils::classBasename($resource)));

                    return $resource->toArray();
                }
            )
            ->values();

        // insert integrated vendors if user has any
        if ($resourceType === 'facilitator') {
            $integratedVendors = IntegratedVendor::where('company_uuid', session('company'))->get();

            if ($integratedVendors->count()) {
                $integratedVendors->each(
                    function ($integratedVendor) use ($results) {
                        $integratedVendor->setAttribute('facilitator_type', 'integrated-vendor');
                        $results->prepend($integratedVendor);
                    }
                );
            }
        }

        // convert to array
        $results = $results->toArray();

        // set resource type
        $results = array_map(
            function ($attributes) use ($resourceType) {
                $attributes['type'] = $resourceType;
                return $attributes;
            },
            $results
        );

        return response()->json([$type => $results]);
    }
}
