<?php

namespace Fleetbase\FleetOps\Models;

use Fleetbase\Models\Model;
use Fleetbase\Traits\HasApiModelBehavior;
use Fleetbase\Traits\HasInternalId;
use Fleetbase\Traits\HasUuid;
use Fleetbase\Traits\TracksApiCredential;
use Fleetbase\Traits\HasPublicId;
use Fleetbase\Traits\SendsWebhooks;
// use Fleetbase\Traits\Searchable;
use Fleetbase\Traits\HasMetaAttributes;
use Fleetbase\Casts\Json;
use Fleetbase\Models\Storefront\Review;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\CausesActivity;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Sluggable\SlugOptions;
use Spatie\Sluggable\HasSlug;

class Contact extends Model
{
    use HasUuid,
        HasPublicId,
        HasApiModelBehavior,
        HasMetaAttributes,
        HasInternalId,
        TracksApiCredential,
        SendsWebhooks,
        HasSlug,
        LogsActivity,
        CausesActivity,
        Notifiable;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'contacts';

    /**
     * The type of public Id to generate
     *
     * @var string
     */
    protected $publicIdType = 'contact';

    /**
     * The attributes that can be queried
     *
     * @var array
     */
    protected $searchableColumns = ['name', 'email', 'phone'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['_key', 'internal_id', 'company_uuid', 'user_uuid', 'photo_uuid', 'name', 'title', 'email', 'phone', 'type', 'meta', 'slug'];

    /**
     * Attributes that is filterable on this model
     *
     * @var array
     */
    protected $filterParams = ['storefront'];


    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'meta' => Json::class,
    ];

    /**
     * Dynamic attributes that are appended to object
     *
     * @var array
     */
    protected $appends = ['photo_url', 'type'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['photo'];

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
    protected static $logName = 'contact';

    /**
     * Get the options for generating the slug.
     */
    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function company()
    {
        return $this->belongsTo(\Fleetbase\Models\Company::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(\Fleetbase\Models\User::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function photo()
    {
        return $this->belongsTo(\Fleetbase\Models\File::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function devices()
    {
        return $this->hasMany(UserDevice::class, 'user_uuid', 'user_uuid');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function reviews()
    {
        return $this->hasMany(Review::class, 'customer_uuid');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function productReviews()
    {
        return $this->hasMany(Review::class, 'customer_uuid')->where('subject_type', 'Fleetbase\Models\Storefront\Product');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function storeReviews()
    {
        return $this->hasMany(Review::class, 'customer_uuid')->where('subject_type', 'Fleetbase\Models\Storefront\Store');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function reviewUploads()
    {
        return $this->hasMany(File::class, 'uploader_uuid')->where('type', 'storefront_review_upload');
    }

    /**
     * @return integer
     */
    public function getReviewsCountAttribute()
    {
        return $this->reviews()->count();
    }

    /**
     * Specifies the user's FCM tokens
     *
     * @return string|array
     */
    public function routeNotificationForFcm()
    {
        return $this->devices->where('platform', 'android')->map(function ($userDevice) {
            return $userDevice->token;
        })->toArray();
    }

    /**
     * Specifies the user's APNS tokens
     *
     * @return string|array
     */
    public function routeNotificationForApn()
    {
        return $this->devices->where('platform', 'ios')->map(function ($userDevice) {
            return $userDevice->token;
        })->toArray();
    }

    /**
     * Get avatar URL attribute.
     */
    public function getPhotoUrlAttribute()
    {
        return static::attributeFromCache($this, 'photo.s3url', 'https://s3.ap-southeast-1.amazonaws.com/flb-assets/static/no-avatar.png');
    }

    public static function findFromCustomerId($publicId)
    {
        if (Str::startsWith($publicId, 'customer')) {
            $publicId = Str::replaceFirst('customer', 'contact', $publicId);
        }

        return static::where('public_id', $publicId)->first();
    }

    /**
     * The users registered client devices.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function facilitatorOrders()
    {
        return $this->hasMany(Order::class, 'facilitator_uuid', 'uuid');
    }

    /**
     * The users registered client devices.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function customerOrders()
    {
        return $this->hasMany(Order::class, 'customer_uuid')->whereNull('deleted_at')->withoutGlobalScopes();
    }

    /**
     * The number of orders by this user.
     * 
     * @return int
     */
    public function getCustomerOrdersCountAttribute()
    {
        return $this->customerOrders()->count();
    }

    public function countStorefrontOrdersFrom($id)
    {
        return Order::where([
            'customer_uuid' => $this->uuid,
            'type' => 'storefront',
            'meta->storefront_id' => $id
        ])->count();
    }

    public function routeNotificationForTwilio()
    {
        return $this->phone;
    }
}
