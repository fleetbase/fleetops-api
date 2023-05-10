<?php

namespace Fleetbase\FleetOps\Models;

use Fleetbase\Models\Model;
use Fleetbase\Traits\HasApiModelBehavior;
use Fleetbase\Scopes\DriverScope;
use Fleetbase\Traits\HasUuid;
use Fleetbase\Traits\TracksApiCredential;
use Fleetbase\Traits\HasInternalId;
use Fleetbase\Traits\HasPublicId;
use Fleetbase\Traits\SendsWebhooks;
use Fleetbase\Casts\Json;
use Fleetbase\FleetOps\Casts\Point;
use Fleetbase\Support\Utils;
use Fleetbase\FleetOps\Support\Utils as FleetOpsUtils;
use Illuminate\Notifications\Notifiable;
use Illuminate\Broadcasting\Channel;
use Illuminate\Support\Facades\DB;
use Grimzy\LaravelMysqlSpatial\Eloquent\SpatialTrait;
use Spatie\Activitylog\Traits\CausesActivity;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Sluggable\SlugOptions;
use Spatie\Sluggable\HasSlug;

class Driver extends Model
{
    use HasUuid, HasPublicId, HasInternalId, TracksApiCredential, HasApiModelBehavior, Notifiable, SendsWebhooks, SpatialTrait, HasSlug, LogsActivity, CausesActivity;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'drivers';

    /**
     * The type of public Id to generate
     *
     * @var string
     */
    protected $publicIdType = 'driver';

    /**
     * The attributes that can be queried
     *
     * @var array
     */
    protected $searchableColumns = ['drivers_license_number', 'user.name', 'user.email', 'user.phone'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        '_key',
        'internal_id',
        'user_uuid',
        'company_uuid',
        'vehicle_uuid',
        'vendor_uuid',
        'current_job_uuid',
        'auth_token',
        'signup_token_used',
        'drivers_license_number',
        'location',
        'heading',
        'bearing',
        'altitude',
        'speed',
        'country',
        'currency',
        'city',
        'online',
        'slug',
        'status',
    ];

    /**
     * The attributes that are spatial columns.
     *
     * @var array
     */
    protected $spatialFields = ['location'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'meta' => Json::class,
        'location' => Point::class,
        'online' => 'boolean',
    ];

    /**
     * Relationships to auto load with driver
     *
     * @var array
     */
    protected $with = [];

    /**
     * Dynamic attributes that are appended to object
     *
     * @var array
     */
    protected $appends = [
        'photo_url',
        'name',
        'phone',
        'email',
        'rotation',
        'vehicle_name',
        'vehicle_avatar',
        'vendor_name',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['currentJob', 'vendor', 'vehicle', 'user', 'latitude', 'longitude', 'auth_token'];

    /**
     * Attributes that is filterable on this model
     *
     * @var array
     */
    protected $filterParams = ['vendor', 'facilitator', 'customer', 'fleet'];

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
    protected static $logName = 'driver';

    /**
     * Get the options for generating the slug.
     */
    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('drivers_license_number')
            ->saveSlugsTo('slug');
    }

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(new DriverScope());
    }

    /**
     * The image file assosciated with the category.
     */
    public function user()
    {
        return $this->belongsTo(\Fleetbase\Models\User::class)->select(['uuid', 'avatar_uuid', 'name', 'phone', 'email'])->without(['driver'])->withTrashed();
    }

    /**
     * The image file assosciated with the category.
     */
    public function company()
    {
        return $this->belongsTo(\Fleetbase\Models\Company::class);
    }

    /**
     * The vehicle assigned to driver.
     */
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class)->select([
            'uuid',
            'public_id',
            'year',
            'model',
            'model_make_display',
            'model',
            'trim',
            'plate_number',
            DB::raw("CONCAT(vehicles.year, ' ', vehicles.model_make_display, ' ', vehicles.model, ' ', vehicles.trim, ' ', vehicles.plate_number) AS display_name")
        ]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function vendor()
    {
        return $this->belongsTo(Vendor::class)->select(['id', 'uuid', 'public_id', 'name']);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function currentJob()
    {
        return $this->belongsTo(Order::class)->select(['id', 'uuid', 'public_id', 'payload_uuid', 'driver_assigned_uuid'])->without(['driver']);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function currentOrder()
    {
        return $this->belongsTo(Order::class, 'current_job_uuid')->select(['id', 'uuid', 'public_id', 'payload_uuid', 'driver_assigned_uuid'])->without(['driver']);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function jobs()
    {
        return $this->hasMany(Order::class, 'driver_assigned_uuid')->without(['driver']);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'driver_assigned_uuid')->without(['driver']);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function pings()
    {
        return $this->hasMany(Ping::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function positions()
    {
        return $this->hasMany(Position::class, 'subject_uuid');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function fleets()
    {
        return $this->hasManyThrough(Fleet::class, FleetDriver::class, 'driver_uuid', 'uuid', 'uuid', 'fleet_uuid');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function devices()
    {
        return $this->hasMany(UserDevice::class, 'user_uuid', 'user_uuid');
    }

    /**
     * Specifies the user's FCM tokens
     *
     * @return array
     */
    public function routeNotificationForFcm()
    {
        return $this->devices
            ->where('platform', 'android')->map(
                function ($userDevice) {
                    return $userDevice->token;
                }
            )
            ->toArray();
    }

    /**
     * Specifies the user's APNS tokens
     *
     * @return array
     */
    public function routeNotificationForApn()
    {
        return $this->devices
            ->where('platform', 'ios')->map(
                function ($userDevice) {
                    return $userDevice->token;
                }
            )->toArray();
    }

    /**
     * The channels the user receives notification broadcasts on.
     *
     * @return array
     */
    public function receivesBroadcastNotificationsOn()
    {
        $channels = [new Channel('driver.' . $this->public_id), new Channel('driver.' . $this->uuid)];

        if (isset($this->company)) {
            $channels[] = new Channel('company.' . $this->company->uuid);
            $channels[] = new Channel('company.' . $this->company->public_id);
        }

        return $channels;
    }

    /**
     * Get the drivers rotation.
     */
    public function getRotationAttribute()
    {
        return round($this->heading / 360 + 180);
    }

    /**
     * Get assigned vehicle assigned name.
     */
    public function getCurrentJobIdAttribute()
    {
        return $this->fromCache('currentJob.public_id');
    }

    /**
     * Get assigned vehicle assigned name.
     */
    public function getVehicleNameAttribute()
    {
        return $this->fromCache('vehicle.display_name');
    }

    /**
     * Get assigned vehicles public ID.
     */
    public function getVehicleIdAttribute()
    {
        return $this->fromCache('vehicle.public_id');
    }

    /**
     * Get assigned vehicles public ID.
     */
    public function getVehicleAvatarAttribute()
    {
        if ($this->isVehicleNotAssigned()) {
            return Vehicle::getAvatar();
        }

        return $this->fromCache('vehicle.avatar_url');
    }

    /**
     * Get drivers vendor ID.
     */
    public function getVendorIdAttribute()
    {
        return $this->fromCache('vendor.public_id');
    }

    /**
     * Get drivers vendor name.
     */
    public function getVendorNameAttribute()
    {
        return $this->fromCache('vendor.name');
    }

    /**
     * Get drivers photo URL attribute.
     */
    public function getPhotoUrlAttribute()
    {
        return $this->fromCache('user.avatarUrl');
    }

    /**
     * Get drivers name
     */
    public function getNameAttribute()
    {
        return $this->fromCache('user.name');
    }

    /**
     * Get drivers phone number
     */
    public function getPhoneAttribute()
    {
        return $this->fromCache('user.phone');
    }

    /**
     * Get drivers email
     */
    public function getEmailAttribute()
    {
        return $this->fromCache('user.email');
    }

    /**
     * Unassigns the current order from the driver if a driver is assigned.
     *
     * @return bool True if the driver was unassigned and the changes were saved, false otherwise
     */
    public function unassignCurrentOrder()
    {
        if (!empty($this->driver_assigned_uuid)) {
            $this->driver_assigned_uuid = null;
            return $this->save();
        }

        return false;
    }

    /**
     * Assign a vehicle to driver.
     *
     * @param Vehicle $vehicle
     * @return void
     */
    public function assignVehicle(Vehicle $vehicle)
    {
        // auto: unassign vehicle from other drivers
        static::where('vehicle_uuid', $vehicle->uuid)->update(['vehicle_uuid' => null]);

        // set this vehicle
        $this->vehicle_uuid = $vehicle->uuid;
        $this->setRelation('vehicle', $vehicle);
        $this->save();

        return $this;
    }

    /**
     * Checks if the vehicle is assigned to the driver.
     *
     * @return bool True if the vehicle is assigned, false otherwise
     */
    public function isVehicleAssigned()
    {
        return $this->isVehicleNotAssigned() === false;
    }

    /**
     * Checks if the vehicle is not assigned to the driver.
     *
     * @return bool True if the vehicle is not assigned, false otherwise
     */
    public function isVehicleNotAssigned()
    {
        return !$this->vehicle_uuid;
    }

    /**
     * Updates the position of the driver, creating a new Position record if
     * the driver has moved more than 100 meters or if it's their first recorded position.
     *
     * @param Order|null $order The order to consider when updating the position (default: null)
     * @return Position|null The created Position object, or null if no new position was created
     */
    public function updatePosition(?Order $order = null): ?Position
    {
        $position = null;
        $lastPosition = $this->positions()->whereCompanyUuid(session('company'))->latest()->first();

        // get the drivers current order
        $currentOrder = $order ?? $this->currentOrder()->with(['payload'])->first();
        $destination = $currentOrder ? $currentOrder->payload->getPickupOrCurrentWaypoint() : null;

        $positionData = [
            'company_uuid' => session('company'),
            'subject_uuid' => $this->uuid,
            'subject_type' => Utils::getMutationType($this),
            'coordinates' => $this->location,
            'altitude' => $this->altitude,
            'heading' => $this->heading,
            'speed' => $this->speed
        ];

        if ($currentOrder) {
            $positionData['order_uuid'] = $currentOrder->uuid;
        }

        if ($destination) {
            $positionData['destination_uuid'] = $destination->uuid;
        }

        $isFirstPosition = !$lastPosition;
        $isPast100Meters = $lastPosition && FleetOpsUtils::vincentyGreatCircleDistance($this->location, $lastPosition->coordinates) > 100;
        $position = null;

        // create the first position
        if ($isFirstPosition || $isPast100Meters) {
            $position = Position::create($positionData);
        }

        return $position;
    }
}
