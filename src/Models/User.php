<?php

namespace Fleetbase\Expansions;

use Fleetbase\Build\Expansion;
use Fleetbase\Models\Driver;

class User implements Expansion
{
    /**
     * Get the target class to expand.
     *
     * @return string|Class
     */
    public static function target()
    {
        return \Fleetbase\Models\User::class;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function driver()
    {
        return function () {
            return $this->hasOne(Driver::class)->without('user');
        };
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function currentDriverSession()
    {
        return function () {
            return $this->driver()->where('company_uuid', session('company'));
        };
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function driverProfiles()
    {
        return function () {
            return $this->hasMany(Driver::class)->without('user');
        };
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function customer()
    {
        return function () {
            return $this->hasOne(Contact::class)->where('type', 'customer')->without('user');
        };
    }
}
