<?php

namespace Fleetbase\FleetOps\Support;

use Fleetbase\Support\Utils as FleetbaseUtils;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class Utils
{
    /**
     * A constant multiplier used to calculate driving time from distance.
     * 
     * @var float
     */
    public const DRIVING_TIME_MULTIPLIER = 7.2;

    /**
     * Get a formatted string representation of a place's address.
     *
     * @param \Fleetbase\FleetOps\Models\Place $place The place object to retrieve the address from.
     * @param bool $useHtml Whether to format the address as HTML (default is false).
     * @param array|string $except An array of keys to exclude from the address (default is an empty array).
     * @return string The formatted address string.
     */
    public static function getAddressStringForPlace(\Fleetbase\FleetOps\Models\Place $place, $useHtml = false, $except = [])
    {
        $address = $useHtml ? '<address>' : '';
        $parts = collect([
            'name',
            'street1',
            'street2',
            'city',
            'province',
            'postal_code',
            'country_name'
        ])->filter(function ($part) use ($except) {
            return is_array($except) ? !in_array($part, $except) : true;
        })->values();
        $numberOfParts = $parts->count();
        $addressValues = [];
        $seperator = $useHtml ? '<br>' : ' - ';

        for ($i = 0; $i < $numberOfParts; $i++) {
            $key = $parts[$i];
            $value = strtoupper(data_get($place, $key)) ?? null;

            // if value empty skip or value equal to last value skip
            if (empty($value) || in_array($value, $addressValues) || (Str::contains(data_get($place, 'street1'), $value) && $key !== 'street1')) {
                continue;
            }

            $addressValues[$key] = $value;
        }

        foreach ($addressValues as $key => $value) {
            if ($key === array_key_last($addressValues)) {
                $seperator = '';
            }

            if ($useHtml && in_array($key, ['street1', 'street2', 'postal_code'])) {
                $seperator = '<br>';
            }

            $address .= strtoupper($value) . $seperator;
            $seperator = ', ';
        }

        if ($useHtml) {
            $address .= '</address>';
        }

        return $address;
    }

    /**
     * Unpack a binary string representing a geographic point into an array of values.
     *
     * @param string $binaryString The binary string to unpack.
     * @return array An array of values representing the geographic point, with keys for 'order', 'type', 'lat', and 'lon'.
     */
    public static function unpackPoint(string $bindaryString): array
    {
        return unpack('x/x/x/x/corder/Ltype/dlat/dlon', $bindaryString);
    }

    /**
     * Unpacks a mysql POINT column from binary to array
     *
     * @param string $rawPoint
     * @return \Grimzy\LaravelMysqlSpatial\Types\Point
     */
    public static function mysqlPointAsGeometry(string $rawPoint): \Grimzy\LaravelMysqlSpatial\Types\Point
    {
        $coordinates = static::unpackPoint($rawPoint);

        return new \Grimzy\LaravelMysqlSpatial\Types\Point($coordinates['lon'], $coordinates['lat']);
    }

    /**
     * Determines if a given number is a valid latitude value in the range of -90 to 90 degrees.
     *
     * @param mixed $num The number to check for latitude validity.
     * @return bool True if the number is a valid latitude value, false otherwise.
     */
    public static function isLatitude($num): bool
    {
        if (!is_numeric($num) || $num === null) {
            return false;
        }

        // cast to float
        $num = (float) $num;

        return is_finite($num) && $num >= -90 && $num <= 90;
    }

    /**
     * Determines if a given number is a valid longitude value in the range of -180 to 180 degrees.
     *
     * @param mixed $num The number to check for longitude validity.
     * @return bool True if the number is a valid longitude value, false otherwise.
     */
    public static function isLongitude($num): bool
    {
        if (!is_numeric($num) || is_null($num)) {
            return false;
        }

        // cast to float
        $num = (float) $num;

        return is_finite($num) && $num >= -180 && $num <= 180;
    }


    /**
     * Cleans a string containing a coordinate value by removing all non-numeric and non-period characters.
     *
     * @param string $string The string to clean.
     * @return string The cleaned string containing only numeric and period characters.
     */
    public static function cleanCoordinateString($string)
    {
        return str_replace([' ', ',', ';', ':', '-', '+', '/', '\\', '(', ')', '[', ']', '{', '}', '_', '|', '@', '#', '$', '%', '^', '&', '*', '=', '?', '!', '<', '>', '~', '`', '"', "'", '“', '”'], '', $string);
    }

    /**
     * Determines whether the given input represents valid coordinates.
     *
     * @param mixed $coordinates The input to check for coordinates validity.
     * @return bool True if the input represents valid coordinates, false otherwise.
     */
    public static function isCoordinates($coordinates): bool
    {
        $latitude = null;
        $longitude = null;

        if ($coordinates instanceof \Grimzy\LaravelMysqlSpatial\Eloquent\SpatialExpression) {
            $coordinates = $coordinates->getSpatialValue();
        }

        if ($coordinates instanceof \Fleetbase\FleetOps\Models\Place) {
            $coordinates = $coordinates->location;
        }

        if ($coordinates instanceof \Grimzy\LaravelMysqlSpatial\Types\Point) {
            $latitude = $coordinates->getLat();
            $longitude = $coordinates->getLng();
        }

        if (is_array($coordinates) || is_object($coordinates)) {
            $latitude = FleetbaseUtils::or($coordinates, ['_lat', 'lat', '_latitude', 'latitude', 'x', '0']);
            $longitude = FleetbaseUtils::or($coordinates, ['lon', '_lon', 'long', 'lng', '_lng', '_longitude', 'longitude', 'y', '1']);
        }

        if (is_string($coordinates)) {
            $coords = [];

            if (Str::startsWith($coordinates, 'POINT(')) {
                $coordinates = Str::replaceFirst('POINT(', '', $coordinates);
                $coordinates = Str::replace(')', '', $coordinates);
                $coords = explode(' ', $coordinates);

                if (count($coords) !== 2) {
                    return false;
                }

                $coords = array_reverse($coords);
                $coordinates = null;
            }

            if (Str::contains($coordinates, ',')) {
                $coords = explode(',', $coordinates);
            }

            if (Str::contains($coordinates, '|')) {
                $coords = explode('|', $coordinates);
            }

            if (Str::contains($coordinates, ' ')) {
                $coords = explode(' ', $coordinates);
            }

            if (count($coords) !== 2) {
                return false;
            }

            $latitude = static::cleanCoordinateString($coords[0]);
            $longitude = static::cleanCoordinateString($coords[1]);
        }

        return static::isLatitude($latitude) && static::isLongitude($longitude);
    }

    /**
     * Gets a coordinate property from coordinates.
     *
     * @param mixed $coordinates The coordinates input value to extract a coordinate from.
     * @param string $prop The coordinate property to extract ('latitude' or 'longitude').
     * @return float The extracted coordinate value as a float.
     */
    public static function getCoordinateFromCoordinates($coordinates, $prop = 'latitude'): float
    {
        $latitude = null;
        $longitude = null;

        if ($coordinates instanceof \Grimzy\LaravelMysqlSpatial\Eloquent\SpatialExpression) {
            $coordinates = $coordinates->getSpatialValue();
        }

        if ($coordinates instanceof \Fleetbase\FleetOps\Models\Place) {
            $coordinates = $coordinates->location;
        }

        if ($coordinates instanceof \Grimzy\LaravelMysqlSpatial\Types\Point) {
            $latitude = $coordinates->getLat();
            $longitude = $coordinates->getLng();
        } else if (is_array($coordinates) || is_object($coordinates)) {
            $latitude = FleetbaseUtils::or($coordinates, ['_lat', 'lat', '_latitude', 'latitude', 'x', '0']);
            $longitude = FleetbaseUtils::or($coordinates, ['lon', '_lon', 'long', 'lng', '_lng', '_longitude', 'longitude', 'y', '1']);
        }

        if (is_string($coordinates)) {
            $coords = [];

            if (Str::startsWith($coordinates, 'POINT(')) {
                $coordinates = Str::replaceFirst('POINT(', '', $coordinates);
                $coordinates = Str::replace(')', '', $coordinates);
                $coords = explode(' ', $coordinates);
                $coords = array_reverse($coords);
                $coordinates = null;
            }

            if (Str::contains($coordinates, ',')) {
                $coords = explode(',', $coordinates);
            }

            if (Str::contains($coordinates, '|')) {
                $coords = explode('|', $coordinates);
            }

            if (Str::contains($coordinates, ' ')) {
                $coords = explode(' ', $coordinates);
            }

            $latitude = $coords[0];
            $longitude = $coords[1];
        }

        return $prop === 'latitude' ? (float) $latitude : (float) $longitude;
    }

    /**
     * Extracts the latitude coordinate value from a given input value representing a location or set of coordinates.
     *
     * @param mixed $coordinates The input value representing a location or set of coordinates.
     * @return float The extracted latitude coordinate value as a float.
     */
    public static function getLatitudeFromCoordinates($coordinates): float
    {
        return static::getCoordinateFromCoordinates($coordinates);
    }


    /**
     * Extracts the longitude coordinate value from a given input value representing a location or set of coordinates.
     *
     * @param mixed $coordinates The input value representing a location or set of coordinates.
     * @return float The extracted longitude coordinate value as a float.
     */
    public static function getLongitudeFromCoordinates($coordinates): float
    {
        return static::getCoordinateFromCoordinates($coordinates, 'longitude');
    }

    /**
     * Extracts a point value from a given input value representing a location or set of coordinates.
     *
     * @param mixed $coordinates The input value representing a location or set of coordinates.
     * @return \Grimzy\LaravelMysqlSpatial\Types\Point The extracted point value.
     */
    public static function getPointFromCoordinates($coordinates): \Grimzy\LaravelMysqlSpatial\Types\Point
    {
        if ($coordinates instanceof \Grimzy\LaravelMysqlSpatial\Types\Point) {
            return $coordinates;
        }

        if (is_null($coordinates) || !static::isCoordinates($coordinates)) {
            return new \Grimzy\LaravelMysqlSpatial\Types\Point(0, 0);
        }

        $latitude = static::getLatitudeFromCoordinates($coordinates);
        $longitude = static::getLongitudeFromCoordinates($coordinates);

        return new \Grimzy\LaravelMysqlSpatial\Types\Point($latitude, $longitude);
    }

    /**
     * Converts a point to a WKT (Well-Known Text) representation for SQL insert.
     *
     * @param mixed $point The input value representing a point.
     * @return \Illuminate\Database\Query\Expression The WKT representation of the point as a raw SQL expression.
     */
    public static function parsePointToWkt($point): \Illuminate\Database\Query\Expression
    {
        $wkt = 'POINT(0 0)';

        if ($point instanceof \Grimzy\LaravelMysqlSpatial\Types\Point) {
            $wkt = $point->toWKT();
        }

        if (is_array($point)) {
            $json = json_encode($point);
            $p = \Grimzy\LaravelMysqlSpatial\Types\Point::fromJson($json);

            $wkt = $p->toWkt();
        }

        if (is_string($point)) {
            $p = \Grimzy\LaravelMysqlSpatial\Types\Point::fromString($point);

            $wkt = $p->toWKT();
        }

        // Use the `ST_PointFromText` function to convert the WKT representation to a SQL expression.
        return DB::raw("(ST_PointFromText('$wkt', 0, 'axis-order=long-lat'))");
    }

    /**
     * Converts a raw point binary string to a float pair representing the point's coordinates.
     *
     * @param string $data The binary string representing the point.
     * @return array The float pair representing the point's coordinates.
     */
    public static function rawPointToFloatPair(string $data): array
    {
        // Use the `unpack` function to extract the X and Y coordinates from the binary string.
        $res = unpack("lSRID/CByteOrder/lTypeInfo/dX/dY", $data);

        // Return the float pair representing the point's coordinates.
        return [$res['X'], $res['Y']];
    }


    /**
     * Converts a raw point binary string to a Laravel MySQL Spatial Point instance.
     *
     * @param string $data The binary string representing the point.
     * @return \Grimzy\LaravelMysqlSpatial\Types\Point The Laravel MySQL Spatial Point instance.
     */
    public static function rawPointToPoint(string $data): \Grimzy\LaravelMysqlSpatial\Types\Point
    {
        // Use the `unpack` function to extract the X, Y, and SRID values from the binary string.
        $res = unpack("lSRID/CByteOrder/lTypeInfo/dX/dY", $data);

        // Return a new Laravel MySQL Spatial Point instance with the X, Y, and SRID values.
        return new \Grimzy\LaravelMysqlSpatial\Types\Point($res['X'], $res['Y'], $res['SRID']);
    }

    /**
     * Calculates driving distance and time using Google distance matrix.
     * Returns distance in meters and time in seconds.
     * 
     * @param \Fleetbase\FleetOps\Models\Place|\Grimzy\LaravelMysqlSpatial\Types\Point|array $origin
     * @param \Fleetbase\FleetOps\Models\Place|\Grimzy\LaravelMysqlSpatial\Types\Point|array $destination
     * 
     * @return \Fleetbase\FleetOps\Support\DistanceMatrix
     */
    public static function getDrivingDistanceAndTime($origin, $destination): DistanceMatrix
    {
        if ($origin instanceof \Fleetbase\FleetOps\Models\Place) {
            $origin = FleetbaseUtils::createObject(
                [
                    'latitude' => $origin->location->getLat(),
                    'longitude' => $origin->location->getLng(),
                ]
            );
        } else {
            $point = static::getPointFromCoordinates($origin);
            $origin = FleetbaseUtils::createObject(
                [
                    'latitude' => $point->getLat(),
                    'longitude' => $point->getLng(),
                ]
            );
        }

        if ($destination instanceof \Fleetbase\FleetOps\Models\Place) {
            $destination = FleetbaseUtils::createObject(
                [
                    'latitude' => $destination->location->getLat(),
                    'longitude' => $destination->location->getLng(),
                ]
            );
        } else {
            $point = static::getPointFromCoordinates($destination);
            $destination = FleetbaseUtils::createObject(
                [
                    'latitude' => $point->getLat(),
                    'longitude' => $point->getLng(),
                ]
            );
        }

        $cacheKey = $origin->latitude . ':' . $origin->longitude . ':' . $destination->latitude . ':' . $destination->longitude;

        // check cache for results
        $cachedResult = Redis::get($cacheKey);

        if ($cachedResult) {
            $json = json_decode($cachedResult);

            return $json;
        }

        $response = Http::get(
            'https://maps.googleapis.com/maps/api/distancematrix/json',
            [
                'origins' => $origin->latitude . ',' . $origin->longitude,
                'destinations' => $destination->latitude . ',' . $destination->longitude,
                'mode' => 'driving',
                'key' => env('GOOGLE_MAPS_API_KEY')
            ]
        )->json();

        $distance = data_get($response, 'rows.0.elements.0.distance.value');
        $time = data_get($response, 'rows.0.elements.0.duration.value');

        $result = FleetbaseUtils::createObject(
            [
                'distance' => $distance,
                'time' => $time
            ]
        );

        // cache result
        Redis::set($cacheKey, json_encode($result));

        return new DistanceMatrix($distance, $time);
    }

    /**
     * Calculates driving distance and time using Google distance matrix for multiple origins or destinations.
     * Returns distance in meters and time in seconds.
     *
     * @param Place|Point|array $origins
     * @param Place|Point|array $destinations
     * @return \Fleetbase\FleetOps\Support\DistanceMatrix
     */
    public static function distanceMatrix($origins = [], $destinations = []): DistanceMatrix
    {
        $origins = array_map(
            function ($origin) {
                $point = static::getPointFromCoordinates($origin);
                $origin = FleetbaseUtils::createObject(
                    [
                        'latitude' => $point->getLat(),
                        'longitude' => $point->getLng(),
                    ]
                );

                return $origin;
            },
            $origins
        );

        $destinations = array_map(
            function ($destination) {
                $point = static::getPointFromCoordinates($destination);
                $destination = FleetbaseUtils::createObject(
                    [
                        'latitude' => $point->getLat(),
                        'longitude' => $point->getLng(),
                    ]
                );

                return $destination;
            },
            $destinations
        );

        // get url ready string for origins
        $originsString = implode('|', array_map(
            function ($origin) {
                return $origin->latitude . ',' . $origin->longitude;
            },
            $origins
        ));

        // get url ready string for origins
        $destinationString = implode('|', array_map(
            function ($destination) {
                return $destination->latitude . ',' . $destination->longitude;
            },
            $destinations
        ));

        $cacheKey = md5($originsString . '_' . $destinationString);

        // check cache for results
        $cachedResult = Redis::get($cacheKey);

        if ($cachedResult) {
            $json = json_decode($cachedResult);

            return $json;
        }

        $response = Http::get(
            'https://maps.googleapis.com/maps/api/distancematrix/json',
            [
                'origins' => $originsString,
                'destinations' => $destinationString,
                'mode' => 'driving',
                'key' => env('GOOGLE_MAPS_API_KEY'),
            ]
        )->json();

        $distance = data_get($response, 'rows.0.elements.0.distance.value');
        $time = data_get($response, 'rows.0.elements.0.duration.value');

        $result = FleetbaseUtils::createObject(
            [
                'distance' => $distance,
                'time' => $time,
            ]
        );

        // cache result
        Redis::set($cacheKey, json_encode($result));

        return new DistanceMatrix($distance, $time);
    }

    /**
     * Calculates driving distance and time between two points using Vincenty's formula.
     * Returns distance in meters and time in seconds.
     * 
     * @param \Fleetbase\FleetOps\Models\Place|\Grimzy\LaravelMysqlSpatial\Types\Point|array $origin
     * @param \Fleetbase\FleetOps\Models\Place|\Grimzy\LaravelMysqlSpatial\Types\Point|array $destination
     * 
     * @return DistanceMatrix
     */
    public static function calculateDrivingDistanceAndTime($origin, $destination): DistanceMatrix
    {
        $origin = $origin instanceof \Fleetbase\FleetOps\Models\Place ? $origin->location : static::getPointFromCoordinates($origin);
        $destination = $destination instanceof \Fleetbase\FleetOps\Models\Place ? $destination->location : static::getPointFromCoordinates($destination);

        $distance = Utils::vincentyGreatCircleDistance($origin, $destination);
        $time = round($distance / 100) * self::DRIVING_TIME_MULTIPLIER;

        return new DistanceMatrix($distance, $time);
    }

    /**
     * Format distance in meters to kilometers or meters
     *
     * @param float $meters Distance in meters
     * @param bool $abbreviate Whether to use abbreviated unit or not
     *
     * @return string
     */
    public static function formatMeters(float $meters, bool $abbreviate = true): string
    {
        if ($meters > 1000) {
            $distance = round($meters / 1000, 2);
            $unit = $abbreviate ? 'km' : 'kilometers';
        } else {
            $distance = round($meters);
            $unit = $abbreviate ? 'm' : 'meters';
        }

        return $distance . ' ' . $unit;
    }


    /**
     * Calculates the great-circle distance between two points, with
     * the Vincenty formula. (Using over haversine tdue to antipodal point issues)
     * 
     * https://en.wikipedia.org/wiki/Great-circle_distance#Formulas
     * https://en.wikipedia.org/wiki/Antipodal_point
     * 
     * @param \Grimzy\LaravelMysqlSpatial\Types\Point Starting point
     * @param \Grimzy\LaravelMysqlSpatial\Types\Point Ending point
     * @param float $earthRadius Mean earth radius in [m]
     * @return float Distance between points in [m] (same as earthRadius)
     */
    public static function vincentyGreatCircleDistance(\Grimzy\LaravelMysqlSpatial\Types\Point $from, \Grimzy\LaravelMysqlSpatial\Types\Point $to, float $earthRadius = 6371000): float
    {
        // convert from degrees to radians
        $latFrom = deg2rad($from->getLat());
        $lonFrom = deg2rad($from->getLng());
        $latTo = deg2rad($to->getLat());
        $lonTo = deg2rad($to->getLng());

        $lonDelta = $lonTo - $lonFrom;
        $a = pow(cos($latTo) * sin($lonDelta), 2) +
            pow(cos($latFrom) * sin($latTo) - sin($latFrom) * cos($latTo) * cos($lonDelta), 2);
        $b = sin($latFrom) * sin($latTo) + cos($latFrom) * cos($latTo) * cos($lonDelta);

        $angle = atan2(sqrt($a), $b);
        return $angle * $earthRadius;
    }

    /**
     * Finds the nearest timezone for a given coordinate point.
     *
     * @param \Grimzy\LaravelMysqlSpatial\Types\Point $location
     * @param string $countryCode
     * @return string
     */
    public static function getNearestTimezone(\Grimzy\LaravelMysqlSpatial\Types\Point $location, string $countryCode = ''): string
    {
        $timezoneIds = \DateTimeZone::listIdentifiers(\DateTimeZone::PER_COUNTRY, $countryCode);
        $lat = $location->getLat();
        $long = $location->getLng();
        $minDistance = INF;
        $timezone = 'unknown';

        foreach ($timezoneIds as $timezoneId) {
            $tz = new \DateTimeZone($timezoneId);
            $location = $tz->getLocation();
            $tzLat = $location['latitude'];
            $tzLong = $location['longitude'];

            $theta = $long - $tzLong;
            $distance = sin(deg2rad($lat)) * sin(deg2rad($tzLat)) + cos(deg2rad($lat)) * cos(deg2rad($tzLat)) * cos(deg2rad($theta));
            $distance = acos($distance);
            $distance = abs(rad2deg($distance));

            if ($distance < $minDistance) {
                $minDistance = $distance;
                $timezone = $timezoneId;
            }
        }

        return $timezone;
    }

    /**
     * Determines whether a given vendor ID is an integrated vendor ID or not.
     *
     * @param  string  $id  The vendor ID to check.
     * @return bool  Returns true if the given ID is an integrated vendor ID or is present in the "integrated_vendors" database table for the current company session, false otherwise.
     */
    public static function isIntegratedVendorId(string $id): bool
    {
        if (Str::startsWith($id, 'integrated_vendor_')) {
            return true;
        }

        $providerIds = DB::table('integrated_vendors')
            ->select('provider')
            ->where('company_uuid', session('company'))
            ->distinct()
            ->get()
            ->map(function ($result) {
                return $result->provider;
            })
            ->toArray();

        return in_array($id, $providerIds);
    }
}
