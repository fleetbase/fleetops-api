<?php

namespace Fleetbase\FleetOps\Models;

use Fleetbase\Models\Model;
use Fleetbase\Traits\HasApiModelBehavior;
use Fleetbase\Traits\HasUuid;
use Fleetbase\Traits\HasPublicId;
use Fleetbase\Traits\SendsWebhooks;
use Fleetbase\Traits\TracksApiCredential;
use Fleetbase\FleetOps\Casts\Polygon;
use Grimzy\LaravelMysqlSpatial\Eloquent\SpatialTrait;

class VehicleDevice extends Model
{
    use HasUuid,
        HasPublicId,
        SendsWebhooks,
        TracksApiCredential,
        SpatialTrait,
        HasApiModelBehavior;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'vehicle_devices';

    /**
     * The type of public Id to generate
     *
     * @var string
     */
    protected $publicIdType = 'vehicle';

    /**
     * Attributes that is filterable on this model
     *
     * @var array
     */
    protected $fillable = [
        'uuid',
        'vehicle_uuid',
        'device_id',
        'device_provider',
        'device_type',
        'device_name',
        'device_model',
        'manufacturer',
        'serial_number',
        'installation_date',
        'last_maintenance_date',
        'meta',
        'status',
        'data_frequency',
        'notes',
    ];

    /**
     * Dynamic attributes that are appended to object
     *
     * @var array
     */
    protected $appends = [];

    /**
     * Filterable params.
     *
     * @var array
     */
    protected $filterParams = [];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [];
}
