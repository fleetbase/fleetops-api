<?php

namespace Fleetbase\FleetOps\Http\Controllers\Internal\v1;

use Fleetbase\FleetOps\Http\Controllers\FleetOpsController;
use Fleetbase\Http\Requests\Internal\BulkDeleteRequest;
use Fleetbase\Http\Requests\ExportRequest;
use Fleetbase\Exports\PlaceExport;
use Fleetbase\FleetOps\Models\Place;
use Fleetbase\FleetOps\Support\Utils as FleetOpsUtils;
use Fleetbase\Support\Utils;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Geocoder\Laravel\Facades\Geocoder;
use Geocoder\Provider\GoogleMapsPlaces\GoogleMapsPlaces;
use Geocoder\Query\GeocodeQuery;
use Http\Adapter\Guzzle7\Client;
use Maatwebsite\Excel\Facades\Excel;

class PlaceController extends FleetOpsController
{
    /**
     * The resource to query
     *
     * @var string
     */
    public $resource = 'place';

    /**
     * Quick search places for selection
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function search(Request $request)
    {
        $searchQuery = strtolower($request->input('query'));
        $limit = $request->input('limit', 10);
        $geo = $request->boolean('geo', false);
        $latitude = $request->input('latitude');
        $longitude = $request->input('longitude');

        $results = DB::table('places')
            ->where('company_uuid', session('company'))
            ->whereNull('deleted_at')
            ->where(
                function ($q) use ($searchQuery) {
                    if (Utils::notEmpty($searchQuery)) {
                        $q->orWhere(DB::raw('lower(name)'), 'like', '%' . $searchQuery . '%');
                        $q->orWhere(DB::raw('lower(street1)'), 'like', '%' . $searchQuery . '%');
                        $q->orWhere(DB::raw('lower(street2)'), 'like', '%' . $searchQuery . '%');
                        $q->orWhere(DB::raw('lower(country)'), 'like', '%' . $searchQuery . '%');
                        $q->orWhere(DB::raw('lower(province)'), 'like', '%' . $searchQuery . '%');
                        $q->orWhere(DB::raw('lower(postal_code)'), 'like', '%' . $searchQuery . '%');
                    }
                }
            )
            ->limit($limit)
            ->orderBy('name', 'desc')
            ->get()
            ->map(
                function ($place) {
                    $place = (array) $place;
                    $place['location'] = FleetOpsUtils::mysqlPointAsGeometry($place['location']);
                    $place = new Place($place);
                    $place->address = $place->toAddressString();

                    return $place;
                }
            )
            ->values();

        if ($geo && $searchQuery) {
            $httpClient = new Client();
            $provider = new \Geocoder\Provider\GoogleMaps\GoogleMaps($httpClient, null, env('GOOGLE_MAPS_API_KEY'));
            $geocoder = new \Geocoder\StatefulGeocoder($provider, 'en');

            if ($latitude && $longitude) {
                $geoResults = $geocoder->geocodeQuery(
                    GeocodeQuery::create($searchQuery)
                        ->withData('mode', GoogleMapsPlaces::GEOCODE_MODE_SEARCH)
                        ->withData('location', "$latitude, $longitude")
                );

                $geoResults = collect($geoResults->all());
            } else {
                $geoResults = Geocoder::geocode($searchQuery)->get();
            }

            $geoResults = $geoResults
                ->map(
                    function ($googleAddress) {
                        return Place::createFromGoogleAddress($googleAddress);
                    }
                )
                ->values();

            $results = $results->merge($geoResults);
        }

        $results = $results
            ->unique('street1')
            ->sortBy('updated_at')
            ->values()
            ->toArray();

        return response()->json($results);
    }

    /**
     * Search using geocoder for addresses.
     *
     * @param  \Illuminate\Http\Request  $query
     * @return \Illuminate\Http\Response
     */
    public function geocode(ExportRequest $request)
    {
        $searchQuery = urldecode(strtolower($request->input('query')));
        $latitude = $request->input('latitude') ?? false;
        $longitude = $request->input('longitude') ?? false;

        $httpClient = new Client();
        $provider = new \Geocoder\Provider\GoogleMaps\GoogleMaps($httpClient, null, env('GOOGLE_MAPS_API_KEY'));
        $geocoder = new \Geocoder\StatefulGeocoder($provider, 'en');

        if ($latitude && $longitude) {
            $geoResults = $geocoder->geocodeQuery(
                GeocodeQuery::create($searchQuery)
                    ->withData('mode', GoogleMapsPlaces::GEOCODE_MODE_SEARCH)
                    ->withData('location', "$latitude, $longitude")
            );

            $geoResults = collect($geoResults->all());
        } else {
            $geoResults = Geocoder::geocode($searchQuery)->get();
        }

        return $geoResults
            ->map(
                function ($googleAddress) {
                    return Place::createFromGoogleAddress($googleAddress);
                }
            )
            ->values();
    }

    /**
     * Export the places to excel or csv
     *
     * @param  \Illuminate\Http\Request  $query
     * @return \Illuminate\Http\Response
     */
    public function export(ExportRequest $request)
    {
        $format = $request->input('format', 'xlsx');
        $fileName = trim(Str::slug('places-' . date('Y-m-d-H:i')) . '.' . $format);

        return Excel::download(new PlaceExport(), $fileName);
    }

    /**
     * Bulk deletes resources.
     *
     * @param  \Fleetbase\Http\Requests\Internal\BulkDeleteRequest $request
     * @return \Illuminate\Http\Response
     */
    public function bulkDelete(BulkDeleteRequest $request)
    {
        $ids = $request->input('ids', []);

        if (!$ids) {
            return response()->error('Nothing to delete.');
        }

        /** 
         * @var \Fleetbase\Models\Place 
         */
        $count = Place::whereIn('uuid', $ids)->count();
        $deleted = Place::whereIn('uuid', $ids)->delete();

        if (!$deleted) {
            return response()->error('Failed to bulk delete places.');
        }

        return response()->json(
            [
                'status' => 'OK',
                'message' => 'Deleted ' . $count . ' places',
            ],
            200
        );
    }
}
