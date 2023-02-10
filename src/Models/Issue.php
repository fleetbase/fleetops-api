<?php

namespace Fleetbase\Models;

use Fleetbase\Traits\HasUuid;
use Fleetbase\Traits\TracksApiCredential;
use Fleetbase\Traits\HasPublicId;
use Fleetbase\Support\Utils;
use Fleetbase\Traits\HasApiModelBehavior;
use Grimzy\LaravelMysqlSpatial\Eloquent\SpatialTrait;
use Grimzy\LaravelMysqlSpatial\Types\Geometry;

class Issue extends Model
{
    use HasUuid, HasPublicId, TracksApiCredential, SpatialTrait, HasApiModelBehavior;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'issues';

    /**
     * The type of public Id to generate
     *
     * @var string
     */
    protected $publicIdType = 'issue';

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
    protected $fillable = [
        '_key',
        'company_uuid',
        'reported_by_uuid',
        'vehicle_uuid',
        'assigned_to_uuid',
        'issue_id',
        'odometer',
        'location',
        'latitude',
        'longitude',
        'type',
        'report',
        'priority',
        'resolved_at',
        'status',
    ];

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
    protected $appends = [];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [];

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
     * User who reported issue
     *
     * @var Model
     */
    public function reportedBy()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * User assigned to issue
     *
     * @var Model
     */
    public function assignedTo()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Vehicle reported from or for
     *
     * @var Model
     */
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }
}
