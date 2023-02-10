<?php

namespace Fleetbase\Models;

use Brick\Geo\IO\GeoJSONReader;
use Brick\Geo\Point as GeoPoint;
use Illuminate\Support\Carbon;
use Fleetbase\Scopes\PlaceScope;
use Fleetbase\Traits\HasUuid;
use Fleetbase\Traits\HasPublicId;
use Fleetbase\Traits\TracksApiCredential;
use Fleetbase\Traits\HasApiModelBehavior;
use Fleetbase\Traits\SendsWebhooks;
use Fleetbase\Support\Utils;
use Fleetbase\Support\Resp;
use Fleetbase\Casts\Json;
use Fleetbase\Casts\Point as SpatialPointCast;
use Fleetbase\Traits\HasMetaAttributes;
use Illuminate\Database\QueryException;
use Geocoder\Laravel\Facades\Geocoder;
use Geocoder\Provider\GoogleMaps\Model\GoogleAddress;
use Grimzy\LaravelMysqlSpatial\Eloquent\SpatialExpression;
use Grimzy\LaravelMysqlSpatial\Eloquent\SpatialTrait;
use Grimzy\LaravelMysqlSpatial\Types\Point;
use Illuminate\Support\Facades\DB;
// use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Place extends Model
{
    use HasUuid, HasPublicId, HasApiModelBehavior, SendsWebhooks, TracksApiCredential, SpatialTrait, HasMetaAttributes;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'places';

    // /**
    //  * Overwrite table to include databse name
    //  */
    // public function __construct()
    // {
    //     $this->table = DB::connection($this->conntaction)->getDatabaseName() . '.' . $this->getTable();
    // }

    /**
     * The type of public Id to generate
     *
     * @var string
     */
    protected $publicIdType = 'place';

    /**
     * The attributes that can be queried
     *
     * @var array
     */
    protected $searchableColumns = ['name', 'country', 'city', 'postal_code'];

    /**
     * The attributes that are spatial columns.
     *
     * @var array
     */
    protected $spatialFields = ['location'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        '_key',
        '_import_id',
        'company_uuid',
        'owner_uuid',
        'owner_type',
        'name',
        'type',
        'street1',
        'street2',
        'city',
        'province',
        'postal_code',
        'neighborhood',
        'district',
        'building',
        'security_access_code',
        'country',
        // 'latitude',
        // 'longitude',
        'location',
        'meta',
        'phone'
    ];

    /**
     * Dynamic attributes that are appended to object
     *
     * @var array
     */
    protected $appends = ['country_name', 'owner_is_vendor', 'owner_is_contact', 'address', 'address_html', 'owner_name'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'id',
        '_key',
        // 'company_uuid',
        'connect_company_uuid',
        'owner_uuid',
        'owner_type',
        'latitude',
        'longitude',
        'owner'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'meta' => Json::class,
        'location' => SpatialPointCast::class,
    ];

    /**
     * Attributes that is filterable on this model
     *
     * @var array
     */
    protected $filterParams = ['vendor', 'contact'];

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(new PlaceScope());
    }

    /**
     * Get all data on the country the place exists in
     *
     * @var array
     */
    public function getCountryDataAttribute()
    {
        return [];
        // if (Utils::isEmpty($this->uuid)) {
        //     return Utils::getCountryData($this->country);
        // }

        // return static::attributeFromCache($this, 'country_data', function () {
        //     return Utils::getCountryData($this->country);
        // });
    }

    /**
     * returns the full country name
     * @return string
     */
    public function getCountryNameAttribute()
    {
        if (Utils::isEmpty($this->uuid)) {
            return Utils::get($this, 'country_data.name.common');
        }

        return static::attributeFromCache($this, 'country_data.name.common');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function owner()
    {
        return $this->morphTo(__FILE__, 'owner_type', 'owner_uuid')->withDefault([
            'name' => 'N/A'
        ]);
    }

    /**
     * The full place address as a string
     *
     * @var string
     */
    public function getAddressString($useHtml = false)
    {
        return $this->toAddressString($useHtml);
    }

    /**
     * The vendor's address as an HTML string
     *
     * @return string
     */
    public function getAddressHtmlAttribute()
    {
        return $this->getAddressString(true);
    }

    /**
     * The vendor's address as an string
     *
     * @return string
     */
    public function getAddressAttribute()
    {
        return $this->getAddressString();
    }

    /**
     * The vendor of this address if any
     *
     * @return string
     */
    public function getOwnerNameAttribute()
    {
        return null;
        // return static::attributeFromCache($this, 'owner.name');
        // return $this->owner ? $this->owner->name : null;
    }

    /**
     * True of the contact is a vendor `customer_is_vendor`
     *
     * @var boolean
     */
    public function getOwnerIsVendorAttribute()
    {
        return $this->owner_type === 'Fleetbase\\Models\\Vendor';
    }

    /**
     * True of the customer is a contact `customer_is_contact`
     *
     * @var boolean
     */
    public function getOwnerIsContactAttribute()
    {
        return $this->owner_type === 'Fleetbase\\Models\\Contact';
    }

    /**
     * Returns a geos applicable Point instance from the location.
     *
     * @return Brick\Geo\Point
     */
    public function getLocationAsPoint()
    {
        if ($this->location instanceof Point) {
            $json = $this->location->toJson();
            $reader = new GeoJSONReader();

            return $reader->read($json);
        }

        if ($this->location instanceof SpatialExpression) {
            $point = $this->location->getSpatialValue();

            return GeoPoint::fromText($point);
        }

        $json = (new Point(0, 0))->toJson();
        $reader = new GeoJSONReader();

        return $reader->read($json);
    }

    /**
     * Fills empty address attributes with google address attributes.
     *
     * @param GoogleAddress $address
     * @return void
     */
    public function fillWithGoogleAddress(GoogleAddress $address)
    {
        $formattedAddress = $address->getFormattedAddress();

        if (Utils::isEmpty($this->street1) && $address) {
            $streetAddress = trim($address->getStreetAddress() ?? $address->getStreetNumber() . ' ' . $address->getStreetName());

            if (empty($streetAddress) && $formattedAddress) {
                // fallback use `formattedAddress`
                $streetAddress = collect(explode(',', $formattedAddress))->take(2)->join(',');
            }

            $this->setAttribute('street1', $streetAddress);
        }

        if (Utils::isEmpty($this->postal_code) && $address) {
            $this->setAttribute('postal_code', $address->getPostalCode());
        }

        if (Utils::isEmpty($this->neighborhood) && $address) {
            $this->setAttribute('neighborhood', $address->getNeighborhood());
        }

        if (Utils::isEmpty($this->city) && $address) {
            $this->setAttribute('city', $address->getLocality());
        }

        if (Utils::isEmpty($this->building) && $address) {
            $this->setAttribute('building', $address->getStreetNumber());
        }

        if (Utils::isEmpty($this->country) && $address) {
            $this->setAttribute('country', $address->getCountry()->getCode());
        }

        if ($coordinates = $address->getCoordinates()) {
            // $this->setAttribute('location', new Point($coordinates->getLongitude(), $coordinates->getLatitude()));
            $this->setAttribute('location', new Point($coordinates->getLatitude(), $coordinates->getLongitude()));
        }

        return $this;
    }

    /**
     * Fills empty address attributes with google address attributes.
     *
     * @param GoogleAddress $address
     * @return void
     */
    public static function getGoogleAddressArray(?GoogleAddress $address)
    {
        $attributes = [];

        if (!$address instanceof GoogleAddress) {
            return $attributes;
        }

        $stretAddress = $address->getStreetAddress() ?? $address->getStreetNumber() . ' ' . $address->getStreetName();
        $coordinates = $address->getCoordinates();

        $attributes['street1'] = $stretAddress;
        $attributes['postal_code'] = $address->getPostalCode();
        $attributes['neighborhood'] = $address->getNeighborhood();
        $attributes['city'] = $address->getLocality();
        $attributes['building'] = $address->getStreetNumber();
        $attributes['country'] = $address->getCountry()->getCode();
        // $attributes['location'] = new Point($coordinates->getLongitude(), $coordinates->getLatitude());
        $attributes['location'] = new Point($coordinates->getLatitude(), $coordinates->getLongitude());

        return $attributes;
    }

    public static function createFromGoogleAddress(GoogleAddress $address, $saveInstance = false)
    {
        $instance = new Place();
        $instance->fillWithGoogleAddress($address);
        $addressString = $instance->getAddressString();

        if ($saveInstance) {
            try {
                $instance->save();
            } catch (QueryException $e) {
                return Resp::error(app()->environment('production') ? 'Failed to create place: ' . $addressString : Utils::sqlExceptionString($e));
            }
        }

        return $instance;
    }

    public static function insertFromGoogleAddress(GoogleAddress $address)
    {
        $values = static::getGoogleAddressArray($address);

        return static::insertGetUuid($values);
    }

    public static function createFromGeocodingLookup(string $address, $saveInstance = false)
    {
        $results = Geocoder::geocode($address)->get();

        if (!$results->count() === 0 || !$results->first()) {
            return (new static())->newInstance(['street1' => $address]);
        }

        return static::createFromGoogleAddress($results->first(), $saveInstance);
    }


    public static function insertFromGeocodingLookup(string $address)
    {
        $results = Geocoder::geocode($address)->get();

        if (!$results->count() === 0 || !$results->first()) {
            return static::insertGetId(['street1' => $address]);
        }

        return static::insertFromGoogleAddress($results->first());
    }

    public static function createFromCoordinates($coordinates, $attributes = [], $saveInstance = false)
    {
        $instance = new Place();

        $latitude = Utils::getLatitudeFromCoordinates($coordinates);
        $longitude = Utils::getLongitudeFromCoordinates($coordinates);

        $instance->setAttribute('location', new Point($latitude, $longitude));
        $instance->fill($attributes);

        $results = Geocoder::reverse($latitude, $longitude)->get();

        if ($results->count() === 0) {
            return false;
        }

        $instance->fillWithGoogleAddress($results->first());
        $addressString = $instance->getAddressString();

        if ($saveInstance) {
            try {
                $instance->save();
            } catch (QueryException $e) {
                return Resp::error(app()->environment('production') ? 'Failed to create place: ' . $addressString : Utils::sqlExceptionString($e));
            }
        }

        return $instance;
    }

    public static function insertFromCoordinates($coordinates)
    {
        $attributes = [];

        if ($coordinates instanceof Point) {
            /** @var \Grimzy\LaravelMysqlSpatial\Types\Point $coordinates */
            $attributes['location'] = $coordinates;

            $latitude = $coordinates->getLat();
            $longitude = $coordinates->getLng();
        } else {
            $latitude = Utils::getLatitudeFromCoordinates($coordinates);
            $longitude = Utils::getLongitudeFromCoordinates($coordinates);

            $attributes['location'] = new Point($latitude, $longitude);
        }

        $results = Geocoder::reverse($latitude, $longitude)->get();

        if (!$results->count() === 0) {
            return false;
        }

        $address = static::getGoogleAddressArray($results->first());
        $values = array_merge($attributes, $address);

        return static::insertGetUuid($values);
    }

    public static function createFromMixed($place, $attributes = [], $saveInstance = true)
    {
        if (gettype($place) === 'string') {
            if (Utils::isPublicId($place)) {
                return Place::where('public_id', $place)->first();
            }

            if (Str::isUuid($place)) {
                return Place::where('uuid', $place)->first();
            }

            // ATTEMPT TO FIND BY ADDRESS OR NAME
            $resolvedFromSearch = static::query()->where('company_uuid', session('company'))->where(function ($q) use ($place) {
                $q->where('street1', $place);
                $q->orWhere('name', $place);
            })->first();

            if ($resolvedFromSearch) {
                return $resolvedFromSearch;
            }

            return Place::createFromGeocodingLookup($place, $saveInstance);
        } elseif ($place instanceof Point) {
            return Place::insertFromCoordinates($place, true);
        } elseif (Utils::isCoordinates($place)) {
            return Place::insertFromCoordinates($place, true);
        } elseif (gettype($place) === 'array') {
            if (Utils::isCoordinates($place)) {
                return Place::createFromCoordinates($place, $attributes, $saveInstance);
            }

            if (isset($place['uuid']) && Str::isUuid($place['uuid'])) {
                $existingPlace = Place::where('uuid', $place['uuid'])->first();

                if ($existingPlace) {
                    return $existingPlace;
                }
            }

            return Place::create($place);
        } elseif ($place instanceof GoogleAddress) {
            return static::createFromGoogleAddress($place, $saveInstance);
        }
    }

    public static function insertFromMixed($place)
    {
        if (gettype($place) === 'string') {
            if (Utils::isPublicId($place)) {
                return Place::where('public_id', $place)->first();
            }

            if (Str::isUuid($place)) {
                return Place::where('uuid', $place)->first();
            }

            return Place::insertFromGeocodingLookup($place);
        } elseif ($place instanceof Point) {
            return Place::insertFromCoordinates($place, true);
        } elseif (Utils::isCoordinates($place)) {
            return Place::insertFromCoordinates($place, true);
        } elseif (gettype($place) === 'array') {
            if (Utils::isCoordinates($place)) {
                return Place::insertFromCoordinates($place, true);
            }

            if (isset($place['uuid']) && Str::isUuid($place['uuid']) && Place::where('uuid', $place['uuid'])->exists()) {
                return $place['uuid'];
            }

            $values = $place;

            return static::insertGetUuid($values);
        } elseif ($place instanceof GoogleAddress) {
            return static::insertFromGoogleAddress($place);
        }
    }

    public static function insertGetUuid($values = [])
    {
        $instance = new static();
        $fillable = $instance->getFillable();
        $insertKeys = array_keys($values);
        // clean insert data
        foreach ($insertKeys as $key) {
            if (!in_array($key, $fillable)) {
                unset($values[$key]);
            }
        }

        $values['uuid'] = $uuid = static::generateUuid();
        $values['public_id'] = static::generatePublicId('place');
        $values['created_at'] = Carbon::now()->toDateTimeString();
        $values['company_uuid'] = session('company');
        $values['_key'] = session('api_key') ?? 'console';

        if (isset($values['location'])) {
            $values['location'] = Utils::parsePointToWkt($values['location']);
        }

        // check if place already exists
        $existing = DB::table($instance->getTable())
            ->select(['uuid'])->where([
                'company_uuid' => session('company'),
                'name' => $values['name'] ?? null,
                'street1' => $values['street1'] ?? null
            ])
            ->whereNull('deleted_at')
            ->first();

        if ($existing) {
            unset($values['uuid'], $values['created_at'], $values['_key'], $values['company_uuid']);
            static::where('uuid', $existing->uuid)->update($values);

            return $existing->uuid;
        }

        if (isset($values['meta']) && (is_object($values['meta']) || is_array($values['meta']))) {
            $values['meta'] = json_encode($values['meta']);
        }

        $result = static::insert($values);

        return $result ? $uuid : false;
    }

    public static function createFromImportRow($row, $importId, $country = null)
    {
        $streetNumberAliases = ['street_number', 'number', 'house_number', 'st_number'];
        $street2Aliases = ['street2', 'unit', 'unit_number'];
        $cityAliases = ['city', 'town'];
        $neighborhoodAliases = ['neighborhood', 'district'];
        $stateAliases = ['state', 'province'];
        $postalCodeAliases = ['postal_code', 'postal', 'zip', 'zip_code'];
        $addressAliases = ['street1', 'address', 'street', 'street_address'];
        $phoneNumberAliases = ['phone', 'mobile', 'phone_number', 'number', 'cell', 'cell_phone', 'mobile_number', 'contact_number'];
        $itemsAliases = ['items', 'entities', 'packages', 'passengers', 'products', 'services'];

        $streetNumber = Utils::or($row, $streetNumberAliases);
        $street2 = Utils::or($row, $street2Aliases);
        $city = Utils::or($row, $cityAliases);
        $neighborhood = Utils::or($row, $neighborhoodAliases);
        $state = Utils::or($row, $stateAliases);
        $postalCode = Utils::or($row, $postalCodeAliases);
        $phoneNumber = Utils::or($row, $phoneNumberAliases);
        $address = Utils::or($row, $addressAliases);

        if ($streetNumber && !Str::contains($address, $streetNumber)) {
            $address = $streetNumber . ' ' . $address;
        }

        if ($street2 && !Str::contains($address, $street2)) {
            $address = $address . ' ' . $street2;
        }

        if ($neighborhood && !Str::contains($address, $neighborhood)) {
            $address = $address . ' ' . $neighborhood;
        }

        if ($city && !Str::contains($address, $city)) {
            $address = $address . ' ' . $city;
        }

        if ($state && !Str::contains($address, $state)) {
            $address = $address . ' ' . $state;
        }

        if ($postalCode && !Str::contains($address, $postalCode)) {
            $address = $address . ' ' . $postalCode;
        }

        if ($country && !Str::contains($address, $country)) {
            $address = $address . ' ' . $country;
        }

        if (!$address) {
            return false;
        }

        $place = Place::createFromGeocodingLookup($address, false);

        if (empty($place->street2)) {
            $place->street2 = $street2;
        }

        if (empty($place->neighborhood)) {
            $place->neighborhood = $neighborhood;
        }

        if (empty($place->province)) {
            $place->province = $state;
        }

        if (empty($place->city)) {
            $place->city = $city;
        }

        if (empty($place->postal_code)) {
            $place->postal_code = $postalCode;
        }

        // get meta
        $meta = collect($row)->except(['name', ...$streetNumberAliases, ...$street2Aliases, ...$cityAliases, ...$neighborhoodAliases, ...$stateAliases, ...$postalCodeAliases, ...$addressAliases, ...$phoneNumberAliases, ...$itemsAliases])->toArray();

        $place->setMetas($meta);
        $place->phone = $phoneNumber;
        $place->setAttribute('_import_id', $importId);

        return $place;
    }

    public function toAddressString($except = [], $useHtml = false)
    {
        return Utils::getAddressStringForPlace($this, $useHtml, $except);
    }

    public function isMissing($part)
    {
        return !isset($this->{$part});
    }
}
