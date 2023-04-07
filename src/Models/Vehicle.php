<?php

namespace Fleetbase\FleetOps\Models;

use Fleetbase\Models\Model;
use Fleetbase\Traits\HasUuid;
use Fleetbase\Traits\HasPublicId;
use Fleetbase\Traits\TracksApiCredential;
use Fleetbase\Traits\Searchable;
use Fleetbase\Support\Utils;
use Fleetbase\Casts\Json;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Sluggable\SlugOptions;
use Spatie\Sluggable\HasSlug;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Exception;
use Fleetbase\Traits\HasApiModelBehavior;

class Vehicle extends Model
{
    use HasUuid, HasPublicId, TracksApiCredential, HasApiModelBehavior, Searchable, HasSlug, LogsActivity;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'vehicles';

    /**
     * The type of public Id to generate
     *
     * @var string
     */
    protected $publicIdType = 'vehicle';

    /**
     * The attributes that can be queried
     *
     * @var array
     */
    protected $searchableColumns = ['make', 'model', 'year', 'plate_number', 'slug', 'vin'];

    /**
     * Attributes that is filterable on this model
     *
     * @var array
     */
    protected $filterParams = ['vendor'];

    /**
     * Relationships to auto load with driver
     *
     * @var array
     */
    protected $with = [];

    /**
     * Properties which activity needs to be logged
     *
     * @var array
     */
    protected static $logAttributes = '*';

    /**
     * Do not log empty changed
     *
     * @var boolean
     */
    protected static $submitEmptyLogs = false;

    /**
     * The name of the subject to log
     *
     * @var string
     */
    protected static $logName = 'vehicle';

    /**
     * Get the options for generating the slug.
     */
    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom(['make', 'model', 'year'])
            ->saveSlugsTo('slug');
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'company_uuid',
        'vendor_uuid',
        'photo_uuid',
        'avatar_url',
        'make',
        'model',
        'year',
        'trim',
        'model_0_to_100_kph',
        'model_body',
        'model_co2',
        'model_doors',
        'model_drive',
        'model_engine_bore_mm',
        'model_engine_cc',
        'model_engine_compression',
        'model_engine_cyl',
        'model_engine_fuel',
        'model_engine_position',
        'model_engine_power_ps',
        'model_engine_power_rpm',
        'model_engine_stroke_mm',
        'model_engine_torque_nm',
        'model_engine_torque_rpm',
        'model_engine_valves_per_cyl',
        'model_fuel_cap_l',
        'model_length_mm',
        'model_lkm_city',
        'model_lkm_hwy',
        'model_lkm_mixed',
        'model_make_display',
        'model_seats',
        'model_sold_in_us',
        'model_top_speed_kph',
        'model_transmission_type',
        'model_weight_kg',
        'model_wheelbase_mm',
        'model_width_mm',
        'type',
        'plate_number',
        'vin',
        'vin_data',
        'status',
        'slug',
    ];

    /**
     * Set attributes and defaults
     *
     * @var array
     */
    protected $attributes = [
        'avatar_url' => 'https://flb-assets.s3-ap-southeast-1.amazonaws.com/static/vehicle-icons/mini_bus.svg'
    ];

    /**
     * Dynamic attributes that are appended to object
     *
     * @var array
     */
    protected $appends = ['display_name', 'photo_url', 'driver_name', 'driver_id', 'driver_uuid', 'vendor_name', 'vendor_id'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'model_0_to_100_kph',
        'model_body',
        'model_co2',
        'model_doors',
        'model_drive',
        'model_engine_bore_mm',
        'model_engine_cc',
        'model_engine_compression',
        'model_engine_cyl',
        'model_engine_fuel',
        'model_engine_position',
        'model_engine_power_ps',
        'model_engine_power_rpm',
        'model_engine_stroke_mm',
        'model_engine_torque_nm',
        'model_engine_torque_rpm',
        'model_engine_valves_per_cyl',
        'model_fuel_cap_l',
        'model_length_mm',
        'model_lkm_city',
        'model_lkm_hwy',
        'model_lkm_mixed',
        'model_make_display',
        'model_seats',
        'model_sold_in_us',
        'model_top_speed_kph',
        'model_transmission_type',
        'model_weight_kg',
        'model_wheelbase_mm',
        'model_width_mm',
        'driver',
        'vendor'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'meta' => Json::class,
        'vin_data' => 'object',
    ];

    /**
     * The name generated from make model and year
     *
     * @var string
     */
    public function getDisplayNameAttribute()
    {
        return sprintf('%s %s %s %s %s', $this->year, $this->model_make_display ?? $this->make, $this->model, $this->trim, $this->plate_number);
    }

    /**
     * The image file assosciated with the vehicle.
     */
    public function photo()
    {
        return $this->belongsTo(\Fleetbase\Models\File::class);
    }

    /**
     * Get avatar URL attribute.
     */
    public function getPhotoUrlAttribute()
    {
        return static::attributeFromCache($this, 'photo.s3url', 'https://s3.ap-southeast-1.amazonaws.com/flb-assets/static/vehicle-placeholder.png');
    }

    /**
     * The driver/operator of vehicle
     */
    public function driver()
    {
        return $this->hasOne(Driver::class)->without(['vehicle']);
    }

    /**
     * The vendor assigned to vehicle.
     */
    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Get avatar url
     */
    public function getAvatarUrlAttribute($value)
    {
        if (!$value) {
            return static::getAvatar();
        }

        return $value;
    }

    /**
     * Get the driver's name assigned to vehicle
     */
    public function getDriverNameAttribute()
    {
        return static::attributeFromCache($this, 'driver.name');
    }

    /**
     * Get the driver's public id assigned to vehicle
     */
    public function getDriverIdAttribute()
    {
        return static::attributeFromCache($this, 'driver.public_id');
    }

    /**
     * Get the driver's uuid assigned to vehicle
     */
    public function getDriverUuidAttribute()
    {
        return static::attributeFromCache($this, 'driver.uuid');
    }

    /**
     * Get drivers vendor ID.
     */
    public function getVendorIdAttribute()
    {
        return static::attributeFromCache($this, 'vendor.public_id');
    }

    /**
     * Get drivers vendor name.
     */
    public function getVendorNameAttribute()
    {
        return static::attributeFromCache($this, 'vendor.name');
    }

    /**
     * Get the vehicles model data attributes
     */
    public function getModelDataAttribute()
    {
        $attributes = $this->getFillable();
        $modelAttributes = [];
        foreach ($attributes as $attr) {
            if (Str::startsWith($attr, 'model_')) {
                $modelAttributes[str_replace('model_', '', $attr)] = $this->{$attr};
            }
        }
        return $modelAttributes;
    }

    /**
     * Apply vin vindecoder data to this vehicle
     * 
     * @param \Errorname\VINDecoder\VIN $data
     * 
     * @return \Fleetbase\Models\Vehicle 
     */
    public function applyVinData($data, $andSave = true)
    {
        foreach ($data->available() as $key) {
            $_key = Str::slug($key, '_');
            if ($this->isFillable($_key)) {
                $this->attributes[$_key] = static::attributeFromCache($data, $key);
            }
        }

        // store all vin data
        $this->attributes['vin_data'] = json_encode($data);

        // auto fill the vehicle year with vin data
        if (Utils::notEmpty($data['model_year'])) {
            $this->attributes['year'] = $data['model_year'];
        }

        // auto fill the vehicle type with vin data
        if (Utils::notEmpty($data['product_type'])) {
            $this->attributes['type'] = Str::slug($data['product_type'], '_');
        }

        // auto fill the vehicle 'make', 'model', 'trim' vin data
        foreach (['make', 'model', 'trim'] as $f) {
            if (!empty($vinData[$f])) {
                $this->attributes[$f] = static::attributeFromCache($data, $f);
            }
        }

        return $andSave === true ? $this->save() : $this;
    }

    /**
     * Apply vin vindecoder data to this vehicle
     * 
     * @param string $make
     * 
     * @return \Fleetbase\Models\Vehicle 
     */
    public function applyVehicleData($m = null, $andSave = true)
    {
        $m = $m ?? $this->model;
        // see if were able to get the make
        $makeRecord = DB::table('vehicles_data')
            ->select('model_make_id')
            ->where('model_make_display', $m)
            ->first();
        if ($makeRecord) {
            $makeId = static::attributeFromCache($makeRecord, 'model_make_id');
            $data = DB::table('vehicles_data')
                ->select('*')
                ->where(function ($q) use ($makeId, $m) {
                    if ($makeId) {
                        $q->where('model_make_id', $makeId);
                    }
                    $q->orWhere('model_make_display', $m);
                })
                ->where(function ($q) {
                    if ($this->trim) {
                        $q->where('model_trim', $this->trim);
                    }
                    $q->orWhere('model_trim', '');
                })
                ->where(function ($q) {
                    if ($this->year) {
                        $q->where('model_year', $this->year);
                    }
                    $q->orWhere('model_year', 'like', '%');
                })
                ->where('model_name', 'like', '%' . ($this->model ?? '') . '%')
                ->first();

            if (count($data)) {
                foreach ($data as $property => $value) {
                    // skip year if already set
                    if ($property === 'model_year' && $value !== $this->year) {
                        continue;
                    }
                    // if property is fillable
                    if ($this->isFillable($property)) {
                        $this->attributes[$property] = $value;
                    }
                }
            }
        }

        return $andSave === true ? $this->save() : $this;
    }

    /**
     * Applies all VIN and vehicle data in a single method
     * 
     * @param string $vin
     * 
     * @return \Fleetbase\Models\Vehicle 
     */
    public function applyAllDataFromVin($vin = null, $andSave = false)
    {
        $vinData = null;
        try {
            $vinData = Utils::decodeVin($vin ?? $this->vin);
        } catch (Exception $e) {
            // silently do nothing
        }

        if ($vinData) {
            $this->applyVinData($vinData, false);
        }

        return $this->applyVehicleData(request()->input('model') ?? null, $andSave);
    }

    /**
     * Get an avatar url by key
     * 
     * @param string $key
     * @return string
     */
    public static function getAvatar($key = 'mini_bus')
    {
        return static::getAvatarOptions()->get($key);
    }

    /**
     * Get all avatar options for a vehicle.
     * 
     * @return \Illuminate\Support\Collection
     */
    public static function getAvatarOptions()
    {
        $options = [
            '2_door_truck.svg',
            '3_door_hatchback.svg',
            '4_door_truck.svg',
            '5_door_hatchback.svg',
            'ambulance.svg',
            'convertible.svg',
            'coupe.svg',
            'electric_car.svg',
            'fastback.svg',
            'full_size_suv.svg',
            'hot_hatch.svg',
            'large_ambulance.svg',
            'light_commercial_truck.svg',
            'light_commercial_van.svg',
            'limousine.svg',
            'mid_size_suv.svg',
            'mini_bus.svg',
            'mini_van.svg',
            'muscle_car.svg',
            'police_1.svg',
            'police_2.svg',
            'roadster.svg',
            'sedan.svg',
            'small_3_door_hatchback.svg',
            'small_5_door_hatchback.svg',
            'sportscar.svg',
            'station_wagon.svg',
            'taxi.svg',
        ];

        return collect($options)->mapWithKeys(
            function ($option) {
                $key = str_replace('.svg', '', $option);

                return [$key => Utils::assetFromS3('static/vehicle-icons/' . $option)];
            }
        );
    }
}
