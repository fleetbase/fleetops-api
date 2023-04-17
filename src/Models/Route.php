<?php

namespace Fleetbase\FleetOps\Models;

use Fleetbase\Models\Model;
use Fleetbase\Casts\Json;
use Fleetbase\Traits\HasUuid;
use Fleetbase\Traits\TracksApiCredential;

class Route extends Model
{
    use HasUuid, TracksApiCredential;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'routes';

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
    protected $fillable = ['_key', 'company_uuid', 'order_uuid', 'details', 'total_time', 'total_distance'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'details' => Json::class,
    ];

    /**
     * Dynamic attributes that are appended to object
     *
     * @var array
     */
    protected $appends = ['payload', 'driver', 'order_status', 'order_public_id', 'order_internal_id', 'order_dispatched_at'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['order'];

    /**
     * Order for this Route.
     *
     * @var Model
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Payload for this routes order.
     *
     * @var Model
     */
    public function getPayloadAttribute()
    {
        return $this->fromCache('order.payload');
    }

    /**
     * Driver for this routes order.
     *
     * @var Model
     */
    public function getDriverAttribute()
    {
        return $this->fromCache('order.driverAssigned');
    }

    /**
     * Order status for this route.
     *
     * @var string
     */
    public function getOrderStatusAttribute()
    {
        return $this->fromCache('order.status');
    }

    /**
     * Order id for this route.
     *
     * @var string
     */
    public function getOrderPublicIdAttribute()
    {
        return $this->fromCache('order.public_id');
    }

    /**
     * Order internal id for this route.
     *
     * @var string
     */
    public function getOrderInternalIdAttribute()
    {
        return $this->fromCache('order.internal_id');
    }

    /**
     * Order internal id for this route.
     *
     * @var string
     */
    public function getOrderDispatchedAtAttribute()
    {
        return $this->fromCache('order.dispatched_at');
    }
}
