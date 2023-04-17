<?php

namespace Fleetbase\FleetOps\Models;

use Fleetbase\Models\Model;
use Fleetbase\Support\Utils;
use Fleetbase\Traits\HasApiModelBehavior;
use Fleetbase\Traits\HasUuid;
use Fleetbase\Traits\TracksApiCredential;
use Fleetbase\Traits\HasPublicId;
use Grimzy\LaravelMysqlSpatial\Eloquent\SpatialTrait;
use Grimzy\LaravelMysqlSpatial\Types\Geometry;

class FuelReport extends Model
{
    use HasUuid, TracksApiCredential, HasPublicId, HasApiModelBehavior, SpatialTrait;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'fuel_reports';

    /**
     * The type of public Id to generate
     *
     * @var string
     */
    protected $publicIdType = 'fuel_report';

    /**
     * These attributes that can be queried
     *
     * @var array
     */
    protected $searchableColumns = [];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['company_uuid', 'driver_uuid', 'vehicle_uuid', 'odometer', 'latitude', 'longitude', 'location', 'amount', 'currency', 'volume', 'metric_unit'];

    /**
     * The attributes that are spatial columns.
     * 
     * @var array
     */
    protected $spatialFields = [
        'location'
    ];

    /**
     * Dynamic attributes that are appended to object
     *
     * @var array
     */
    protected $appends = ['vehicle_name', 'driver_name'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['driver', 'vehicle'];

    /**
     * Convert coordinates to spatial geometry
     *
     * @param Object $coordinates
     * @return void
     */
    public function setLocationAttribute($location) {

        if(Utils::exists($location, 'coordinates')) {
            $location['coordinates'] = array_map(function($coord) {
                return (float) $coord;
            }, Utils::get($location, 'coordinates'));
        }

        if(Utils::exists($location, 'bbox')) {
            $location['bbox'] = array_map(function($coord) {
                return (float) $coord;
            }, Utils::get($location, 'bbox'));
        }

        $location = Geometry::fromJson(json_encode($location));

        $this->attributes['location'] = $location;
    }

    /**
     * Set the parcel fee as only numbers
     *
     * @void
     */
    public function setAmountAttribute($value)
    {
        $this->attributes['amount'] = Utils::numbersOnly($value);
    }

    /**
     * Driver who reported
     *
     * @var Model
     */
    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    /**
     * The vehicle reported from
     *
     * @var Model
     */
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Get the driver's name assigned to vehicle
     *
     * @var Model
     */
    public function getDriverNameAttribute()
    {
        return $this->fromCache('driver.name');
    }

    /**
     * Get the vehicless name
     *
     * @var Model
     */
    public function getVehicleNameAttribute()
    {
        return $this->fromCache('vehicle.name');
    }
}
