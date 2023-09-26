<?php

namespace Fleetbase\FleetOps\Models;

use Fleetbase\Models\Model;
use Fleetbase\Traits\HasUuid;
use Fleetbase\Traits\HasPublicId;
use Fleetbase\Traits\TracksApiCredential;
use Fleetbase\Traits\Searchable;
use Fleetbase\FleetOps\Support\Utils;
use Fleetbase\Casts\Json;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Sluggable\SlugOptions;
use Spatie\Sluggable\HasSlug;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
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
        'location',
        'model',
        'year',
        'trim',
        'type',
        'plate_number',
        'vin',
        'vin_data',
        'telematics',
        'status',
        'online',
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
    protected $appends = ['display_name', 'photo_url', 'driver_name', 'vendor_name'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
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
        'telematics' => Json::class,
        'model' => Json::class,
        'vin_data' => Json::class,
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function photo()
    {
        return $this->belongsTo(\Fleetbase\Models\File::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function driver()
    {
        return $this->hasOne(Driver::class)->without(['vehicle']);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function devices()
    {
        return $this->hasMany(VehicleDevices::class);
    }

    /**
     * Get avatar URL attribute.
     */
    public function getPhotoUrlAttribute()
    {
        return data_get($this, 'photo.url', 'https://s3.ap-southeast-1.amazonaws.com/flb-assets/static/vehicle-placeholder.png');
    }

    /**
     * The name generated from make model and year
     *
     * @var string
     */
    public function getDisplayNameAttribute()
    {
        return trim(sprintf('%s %s %s %s %s', $this->year, $this->make, $this->model, $this->trim, $this->plate_number));
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
        return data_get($this, 'driver.name');
    }

    /**
     * Get the driver's public id assigned to vehicle
     */
    public function getDriverIdAttribute()
    {
        return data_get($this, 'driver.public_id');
    }

    /**
     * Get the driver's uuid assigned to vehicle
     */
    public function getDriverUuidAttribute()
    {
        return data_get($this, 'driver.uuid');
    }

    /**
     * Get drivers vendor ID.
     */
    public function getVendorIdAttribute()
    {
        return data_get($this, 'vendor.public_id');
    }

    /**
     * Get drivers vendor name.
     */
    public function getVendorNameAttribute()
    {
        return data_get($this, 'vendor.name');
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
