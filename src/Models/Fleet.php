<?php

namespace Fleetbase\FleetOps\Models;

use Fleetbase\Models\Model;
use Fleetbase\Traits\HasApiModelBehavior;
use Fleetbase\Traits\HasUuid;
use Fleetbase\Traits\HasPublicId;
use Fleetbase\Traits\TracksApiCredential;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Sluggable\SlugOptions;
use Spatie\Sluggable\HasSlug;
use Fleetbase\Traits\Searchable;
use Fleetbase\Traits\SendsWebhooks;

class Fleet extends Model
{
    use HasUuid, HasPublicId, HasApiModelBehavior, TracksApiCredential, SendsWebhooks, Searchable, HasSlug, LogsActivity;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'fleets';

    /**
     * The type of public Id to generate
     *
     * @var string
     */
    protected $publicIdType = 'fleet';

    /**
     * These attributes that can be queried
     *
     * @var array
     */
    protected $searchableColumns = ['name'];

    /**
     * Properties which activity needs to be logged
     *
     * @var array
     */
    protected static $logAttributes = ['name', 'task', 'service_area_uuid', 'zone_uuid'];

    /**
     * Do not log empty changed
     *
     * @var boolean
     */
    protected static $submitEmptyLogs = false;

    /**
     * We only want to log changed attributes
     *
     * @var boolean
     */
    protected static $logOnlyDirty = true;

    /**
     * The name of the subject to log
     *
     * @var string
     */
    protected static $logName = 'fleet';

    /**
     * @return \Spatie\Sluggable\SlugOptions
     */
    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        '_key',
        'public_id',
        'company_uuid',
        'service_area_uuid',
        'zone_uuid',
        'vendor_uuid',
        'parent_fleet_uuid',
        'image_uuid',
        'name',
        'color',
        'task',
        'status',
        'slug'
    ];

    /**
     * Dynamic attributes that are appended to object
     *
     * @var array
     */
    protected $appends = ['photo_url', 'drivers_count', 'drivers_online_count'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function photo()
    {
        return $this->belongsTo(\Fleetbase\Models\File::class, 'image_uuid', 'uuid');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function serviceArea()
    {
        return $this->belongsTo(ServiceArea::class)->select(['uuid', 'public_id', 'name', 'type', 'border']);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function zone()
    {
        return $this->belongsTo(Zone::class)->select(['uuid', 'public_id', 'name', 'border']);
    }
    
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function vendor()
    {
        return $this->belongsTo(Vendor::class)->select(['uuid', 'public_id', 'name']);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parentFleet()
    {
        return $this->belongsTo(Fleet::class)->select(['uuid', 'public_id', 'name']);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function drivers()
    {
        return $this->hasManyThrough(Driver::class, FleetDriver::class, 'fleet_uuid', 'uuid', 'uuid', 'driver_uuid');
    }

    /**
     * Get avatar URL attribute.
     * 
     * @return string
     */
    public function getPhotoUrlAttribute()
    {
        return data_get($this, 'photo.s3url', 'https://s3.ap-northeast-2.amazonaws.com/fleetbase/public/default-fleet.png');
    }

    /**
     * Get the number of drivers in fleet.
     * 
     * @return integer
     */
    public function getDriversCountAttribute()
    {
        return $this->drivers()->count();
    }

    /**
     * Get the number of drivers in fleet.
     * 
     * @return integer
     */
    public function getDriversOnlineCountAttribute()
    {
        return $this->drivers()->where('online', 1)->count();
    }
}
