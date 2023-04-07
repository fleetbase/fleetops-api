<?php

namespace Fleetbase\FleetOps\Models;

use Fleetbase\Models\Model;
use Brick\Geo\IO\GeoJSONReader;
use Fleetbase\Support\Utils;
// use Fleetbase\Support\TimezoneMapService;
use Fleetbase\Casts\Money;
use Fleetbase\Scopes\ServiceRateScope;
use Fleetbase\Support\Algo;
use Fleetbase\Traits\HasApiModelBehavior;
use Fleetbase\Traits\HasUuid;
use Fleetbase\Traits\HasPublicId;
use Fleetbase\Traits\TracksApiCredential;
use Fleetbase\Traits\SendsWebhooks;
use Illuminate\Support\Facades\DB;

class ServiceRate extends Model
{
    use HasUuid, HasPublicId, TracksApiCredential, SendsWebhooks, HasApiModelBehavior;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'service_rates';

    /**
     * The type of public Id to generate
     *
     * @var string
     */
    protected $publicIdType = 'service';

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
        'service_area_uuid',
        'zone_uuid',
        'service_name',
        'service_type',
        'per_meter_flat_rate_fee',
        'per_meter_unit',
        'base_fee',
        'algorithm',
        'rate_calculation_method',
        'has_cod_fee',
        'cod_calculation_method',
        'cod_flat_fee',
        'cod_percent',
        'has_peak_hours_fee',
        'peak_hours_calculation_method',
        'peak_hours_flat_fee',
        'peak_hours_percent',
        'peak_hours_start',
        'peak_hours_end',
        'currency',
        'duration_terms',
        'estimated_days',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'base_fee' => Money::class . ':currency',
        'per_meter_flat_rate_fee' => Money::class . ':currency',
        'cod_flat_fee' => Money::class . ':currency',
        'peak_hours_flat_fee' => Money::class . ':currency',
    ];

    /**
     * Dynamic attributes that are appended to object
     *
     * @var array
     */
    protected $appends = ['service_area_name', 'zone_name'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['serviceArea', 'zone'];

    /**
     * Attributes that is filterable on this model
     *
     * @var array
     */
    protected $filterParams = [];

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(new ServiceRateScope());
    }

    /**
     * Fees for the rate
     *
     * @var Model
     */
    public function rateFees()
    {
        return $this->hasMany(ServiceRateFee::class);
    }

    /**
     * Fees for the rate
     *
     * @var Model
     */
    public function parcelFees()
    {
        return $this->hasMany(ServiceRateParcelFee::class);
    }

    /**
     * Service area for rate.
     *
     * @var Model
     */
    public function serviceArea()
    {
        return $this->belongsTo(ServiceArea::class)->whereNull('deleted_at');
    }

    /**
     * Service area for rate.
     *
     * @var Model
     */
    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    /**
     * Get the service area name
     *
     * @var string
     */
    public function getServiceAreaNameAttribute()
    {
        return static::attributeFromCache($this->serviceArea, 'name');
    }

    /**
     * Get the zone's name
     *
     * @var string
     */
    public function getZoneNameAttribute()
    {
        return static::attributeFromCache($this->zone, 'name');
    }

    /**
     * Determines if rate calulcation method
     */
    public function isRateCalculationMethod($method)
    {
        return $this->rate_calculation_method === $method;
    }

    public function isFixedMeter()
    {
        return $this->rate_calculation_method === 'fixed_meter';
    }

    public function isPerMeter()
    {
        return $this->rate_calculation_method === 'per_meter';
    }

    public function isPerDrop()
    {
        return $this->rate_calculation_method === 'per_drop';
    }

    public function isAlgorithm()
    {
        return $this->rate_calculation_method === 'algo';
    }

    public function isParcelService()
    {
        return $this->service_type === 'parcel';
    }

    public function hasPeakHoursFee()
    {
        return (bool) $this->has_peak_hours_fee;
    }

    public function isWithinPeakHours()
    {
        $currentTime = strtotime(date('H:i'));
        $startTime = strtotime($this->peak_hours_start);
        $endTime = strtotime($this->peak_hours_end);

        return $currentTime >= $startTime && $currentTime <= $endTime;
    }

    public function hasPeakHoursFlatFee()
    {
        return $this->peak_hours_calculation_method === 'flat';
    }

    public function hasPeakHoursPercentageFee()
    {
        return $this->peak_hours_calculation_method === 'percentage';
    }

    public function hasCodFee()
    {
        return (bool) $this->has_cod_fee;
    }

    public function hasCodFlatFee()
    {
        return $this->cod_calculation_method === 'flat';
    }

    public function hasCodPercentageFee()
    {
        return $this->cod_calculation_method === 'percentage';
    }

    public function hasZone()
    {
        $this->loadMissing('zone');

        return (bool) $this->zone;
    }

    public function hasServiceArea()
    {
        $this->loadMissing('serviceArea');

        return (bool) $this->serviceArea;
    }

    public function setServiceRateFees(?array $serviceRateFees = [])
    {
        if (!$serviceRateFees) {
            return $this;
        }

        $iterate = count($serviceRateFees);

        for ($i = 0; $i < $iterate; $i++) {
            // if already has uuid then we just update the record and remove from insert array
            if (!empty($serviceRateFees[$i]['uuid'])) {
                $id = $serviceRateFees[$i]['uuid'];
                $updateableAttributes = collect($serviceRateFees[$i])->except(['uuid', 'created_at', 'updated_at'])->toArray();

                if ($updateableAttributes) {
                    ServiceRateFee::where('uuid', $id)->update($updateableAttributes);
                }

                unset($serviceRateFees[$i]);
                continue;
            }

            $serviceRateFees[$i]['service_rate_uuid'] = $this->uuid;
        }

        $serviceRateFees = collect($serviceRateFees)->filter()->values()->toArray();
        ServiceRateFee::bulkInsert($serviceRateFees);

        return $this;
    }

    public function setServiceRateParcelFees(?array $serviceRateParcelFees = [])
    {
        if (!$serviceRateParcelFees) {
            return $this;
        }

        $iterate = count($serviceRateParcelFees);

        for ($i = 0; $i < $iterate; $i++) {
            // if already has uuid then we just update the record and remove from insert array
            if (isset($serviceRateParcelFees[$i]['uuid'])) {
                $id = $serviceRateParcelFees[$i]['uuid'];
                $updateableAttributes = collect($serviceRateParcelFees[$i])->except(['uuid', 'created_at', 'updated_at'])->toArray();

                if ($updateableAttributes) {
                    ServiceRateParcelFee::where('uuid', $id)->update($updateableAttributes);
                }

                unset($serviceRateParcelFees[$i]);
                continue;
            }

            $serviceRateParcelFees[$i]['service_rate_uuid'] = $this->uuid;
        }

        $serviceRateParcelFees = collect($serviceRateParcelFees)->filter()->values()->toArray();
        ServiceRateParcelFee::bulkInsert($serviceRateParcelFees);

        return $this;
    }

    /**
     * Retrieves all service rates applicable to an array of Point[]
     *
     * @param array|Collection $waypoints
     * @return array
     */
    public static function getServicableForWaypoints($waypoints = []): array
    {
        $reader = new GeoJSONReader();
        $applicableServiceRates = [];
        $serviceRates = static::with(['zone', 'serviceArea'])->get();

        foreach ($serviceRates as $serviceRate) {
            if ($serviceRate->hasServiceArea()) {
                // make sure all waypoints fall within the service area
                foreach ($serviceRate->serviceArea->border as $polygon) {
                    $polygon = $reader->read($polygon->toJson());

                    foreach ($waypoints as $waypoint) {
                        if (!$polygon->contains($waypoint)) {
                            // waypoint outside of service area, not applicable to route
                            continue;
                        }
                    }
                }
            }

            if ($serviceRate->hasZone()) {
                // make sure all waypoints fall within the service area
                foreach ($serviceRate->zone->border as $polygon) {
                    $polygon = $reader->read($polygon->toJson());

                    foreach ($waypoints as $waypoint) {
                        if (!$polygon->contains($waypoint)) {
                            // waypoint outside of zone, not applicable to route
                            continue;
                        }
                    }
                }
            }

            $applicableServiceRates[] = $serviceRate;
        }

        return $applicableServiceRates;
    }

    /**
     * Retrieves all service rates applicable to an array of Point[]
     *
     * @param array|Collection $waypoints
     * @return array
     */
    public static function getServicableForPlaces($places = [], $service = null, $currency = null): array
    {
        $reader = new GeoJSONReader();
        $applicableServiceRates = [];
        $serviceRates = static::with(['zone', 'serviceArea', 'rateFees', 'parcelFees']);

        if ($currency) {
            $serviceRates->where(DB::raw("lower(currency)"), strtolower($currency));
        }

        if ($service) {
            $serviceRates->where('service_type', $service);
        }

        $serviceRates = $serviceRates->get();

        $waypoints = collect($places)->map(function ($place) {
            return $place->getLocationAsPoint();
        });

        foreach ($serviceRates as $serviceRate) {
            if ($serviceRate->hasServiceArea()) {
                // make sure all waypoints fall within the service area
                foreach ($serviceRate->serviceArea->border as $polygon) {
                    $polygon = $reader->read($polygon->toJson());

                    foreach ($waypoints as $waypoint) {
                        if (!$polygon->contains($waypoint)) {
                            // waypoint outside of service area, not applicable to route
                            continue;
                        }
                    }
                }
            }

            if ($serviceRate->hasZone()) {
                // make sure all waypoints fall within the service area
                foreach ($serviceRate->zone->border as $polygon) {
                    $polygon = $reader->read($polygon->toJson());

                    foreach ($waypoints as $waypoint) {
                        if (!$polygon->contains($waypoint)) {
                            // waypoint outside of zone, not applicable to route
                            continue;
                        }
                    }
                }
            }

            $applicableServiceRates[] = $serviceRate;
        }

        return $applicableServiceRates;
    }

    public function pointQuote($pickupPoint, $dropoffPoint, $entities = [])
    {
        $payload = new Payload();
        $payload->entities = $entities;
        $payload->pickup = $pickup = new Place([
            'location' => Utils::getPointFromCoordinates($pickupPoint),
        ]);
        $payload->dropoff = $dropoff = new Place([
            'location' => Utils::getPointFromCoordinates($dropoffPoint),
        ]);

        // calculate distance and time
        $matrix = Utils::getDrivingDistanceAndTime($payload->pickup, $payload->dropoff);

        return $this->quoteFromPreliminaryData($entities, [$pickup, $dropoff], $matrix->distance, $matrix->time);
    }

    public function quoteFromPreliminaryData($entities = [], $waypoints = [], ?int $totalDistance = 0, ?int $totalTime = 0, ?bool $isCashOnDelivery = false)
    {
        $lines = collect();
        $subTotal = $this->base_fee ?? 0;

        $lines->push([
            'details' => 'Base Fee',
            'amount' => Utils::numbersOnly($subTotal),
            'formatted_amount' => Utils::moneyFormat($subTotal, $this->currency),
            'currency' => $this->currency,
            'code' => 'BASE_FEE',
        ]);

        if ($this->isFixedMeter()) {
            $distanceFee = $this->findServiceRateFeeByDistance($totalDistance);

            if ($distanceFee) {
                $subTotal += Utils::numbersOnly($distanceFee->fee);

                $lines->push([
                    'details' => 'Service Fee',
                    'amount' => Utils::numbersOnly($distanceFee->fee),
                    'formatted_amount' => Utils::moneyFormat($distanceFee->fee, $this->currency),
                    'currency' => $this->currency,
                    'code' => 'BASE_FEE',
                ]);
            }
        }

        if ($this->isPerDrop()) {
            $rateFee = $this->findServiceRateFeeByMinMax(count($waypoints));

            if ($rateFee) {
                $subTotal += Utils::numbersOnly($rateFee->fee);

                $lines->push([
                    'details' => 'Service Fee',
                    'amount' => Utils::numbersOnly($rateFee->fee),
                    'formatted_amount' => Utils::moneyFormat($rateFee->fee, $this->currency),
                    'currency' => $this->currency,
                    'code' => 'BASE_FEE',
                ]);
            }
        }

        if ($this->isPerMeter()) {
            $perMeterDistance = $this->per_meter_unit === 'km' ? round($totalDistance / 1000) : $totalDistance;
            $rateFee = $perMeterDistance * $this->per_meter_flat_rate_fee;
            $subTotal += $rateFee;

            $lines->push([
                'details' => 'Service Fee',
                'amount' => Utils::numbersOnly($rateFee),
                'formatted_amount' => Utils::moneyFormat($rateFee, $this->currency),
                'currency' => $this->currency,
                'code' => 'BASE_FEE',
            ]);
        }

        if ($this->isAlgorithm()) {
            $rateFee = Algo::exec(
                $this->algorithm,
                [
                    'distance' => $totalDistance,
                    'time' => $totalTime,
                ],
                true
            );

            $subTotal += Utils::numbersOnly($rateFee);

            $lines->push([
                'details' => 'Service Fee',
                'amount' => Utils::numbersOnly($rateFee),
                'formatted_amount' => Utils::moneyFormat($rateFee, $this->currency),
                'currency' => $this->currency,
                'code' => 'BASE_FEE',
            ]);
        }

        // if parcel fee's add into the base rate
        if ($this->isParcelService()) {
            $parcels = collect($entities)->where('type', 'parcel')->all();

            foreach ($parcels as $parcel) {
                // convert all length units to cm and weight units to grams
                $length = $parcel->length_unit->toUnit('cm');
                $width = $parcel->width_unit->toUnit('cm');
                $height = $parcel->height_unit->toUnit('cm');
                $weight = $parcel->mass_unit->toUnit('g');
                $serviceParcelFee = null;

                // iterate through parcel fees to find where it fits
                foreach ($this->parcelFees as $parcelFee) {
                    $feeLength = $parcelFee->length_unit->toUnit('cm');
                    $feeWidth = $parcelFee->width_unit->toUnit('cm');
                    $feeHeight = $parcelFee->height_unit->toUnit('cm');
                    $feeWeight = $parcelFee->mass_unit->toUnit('g');

                    $previousParcelFee = $parcelFee;

                    if ($length > $feeLength && $width > $feeWidth && $height > $feeHeight && $weight > $feeWeight) {
                        continue;
                    } elseif ($length < $feeLength && $width < $feeWidth && $height < $feeHeight && $weight < $feeWeight) {
                        $serviceParcelFee = $previousParcelFee;
                    } else {
                        $serviceParcelFee = $parcelFee;
                    }
                }

                // if no distance fee use the last
                if ($serviceParcelFee === null) {
                    $serviceParcelFee = $this->parcelFees->sortByDesc()->first();
                }

                $subTotal += $serviceParcelFee->fee;

                $lines->push([
                    'details' => $serviceParcelFee->name . ' parcel fee',
                    'amount' => Utils::numbersOnly($serviceParcelFee->fee),
                    'formatted_amount' => Utils::moneyFormat($serviceParcelFee->fee, $this->currency),
                    'currency' => $this->currency,
                    'code' => 'PARCEL_FEE',
                ]);
            }
        }

        // set the base rate
        $baseRate = $subTotal;

        // if the rate has cod add this into the quote price
        if ($this->hasCodFee() && $isCashOnDelivery) {
            if ($this->hasCodFlatFee()) {
                $subTotal += $codFee = $this->cod_flat_fee;
            } elseif ($this->hasCodPercentageFee()) {
                $subTotal += $codFee = Utils::calculatePercentage($this->cod_percent, $baseRate);
            }

            $lines->push([
                'details' => 'Cash on delivery fee',
                'amount' => Utils::numbersOnly($codFee),
                'formatted_amount' => Utils::moneyFormat($codFee, $this->currency),
                'currency' => $this->currency,
                'code' => 'COD_FEE',
            ]);
        }

        // if this has peak hour fee add in
        if ($this->hasPeakHoursFee() && $this->isWithinPeakHours()) {
            if ($this->hasPeakHoursFlatFee()) {
                $subTotal += $peakHoursFee = $this->peak_hours_flat_fee;
            } elseif ($this->hasPeakHoursPercentageFee()) {
                $subTotal += $peakHoursFee = Utils::calculatePercentage($this->peak_hours_percent, $baseRate);
            }

            $lines->push([
                'details' => 'Peak hours fee',
                'amount' => Utils::numbersOnly($peakHoursFee),
                'formatted_amount' => Utils::moneyFormat($peakHoursFee, $this->currency),
                'currency' => $this->currency,
                'code' => 'PEAK_HOUR_FEE',
            ]);
        }

        return [$subTotal, $lines];
    }

    public function quote(Payload $payload)
    {
        $lines = collect();
        $subTotal = $this->base_fee ?? 0;

        $lines->push([
            'details' => 'Base Fee',
            'amount' => Utils::numbersOnly($subTotal),
            'formatted_amount' => Utils::moneyFormat($subTotal, $this->currency),
            'currency' => $this->currency,
            'code' => 'BASE_FEE',
        ]);

        // Prepare all waypoints and origin and destination
        $waypoints = $payload->getAllStops()->mapInto(Place::class);
        $origin = $waypoints->first();
        $destinations = $waypoints->skip(1)->toArray();

        // Lookup distance matrix for total distance and time
        $distanceMatrix = Utils::distanceMatrix([$origin], $destinations);
        $totalDistance = $distanceMatrix->distance;
        $totalTime = $distanceMatrix->time;

        if ($this->isFixedMeter()) {
            $distanceFee = $this->findServiceRateFeeByDistance($totalDistance);

            if ($distanceFee) {
                $subTotal += Utils::numbersOnly($distanceFee->fee);

                $lines->push([
                    'details' => 'Service Fee',
                    'amount' => Utils::numbersOnly($distanceFee->fee),
                    'formatted_amount' => Utils::moneyFormat($distanceFee->fee, $this->currency),
                    'currency' => $this->currency,
                    'code' => 'BASE_FEE',
                ]);
            }
        }

        if ($this->isPerDrop()) {
            $rateFee = $this->findServiceRateFeeByMinMax(count($waypoints));

            if ($rateFee) {
                $subTotal += Utils::numbersOnly($rateFee->fee);

                $lines->push([
                    'details' => 'Service Fee',
                    'amount' => Utils::numbersOnly($rateFee->fee),
                    'formatted_amount' => Utils::moneyFormat($rateFee->fee, $this->currency),
                    'currency' => $this->currency,
                    'code' => 'BASE_FEE',
                ]);
            }
        }

        if ($this->isPerMeter()) {
            $perMeterDistance = $this->per_meter_unit === 'km' ? round($totalDistance / 1000) : $totalDistance;
            $rateFee = $perMeterDistance * $this->per_meter_flat_rate_fee;
            $subTotal += $rateFee;

            $lines->push([
                'details' => 'Service Fee',
                'amount' => Utils::numbersOnly($rateFee),
                'formatted_amount' => Utils::moneyFormat($rateFee, $this->currency),
                'currency' => $this->currency,
                'code' => 'BASE_FEE',
            ]);
        }

        if ($this->isAlgorithm()) {
            $rateFee = Algo::exec(
                $this->algorithm,
                [
                    'distance' => $totalDistance,
                    'time' => $totalTime,
                ],
                true
            );

            $subTotal += Utils::numbersOnly($rateFee);

            $lines->push([
                'details' => 'Service Fee',
                'amount' => Utils::numbersOnly($rateFee),
                'formatted_amount' => Utils::moneyFormat($rateFee, $this->currency),
                'currency' => $this->currency,
                'code' => 'BASE_FEE',
            ]);
        }

        // if parcel fee's add into the base rate
        if ($this->isParcelService()) {
            $parcels = $payload->entities->where('type', 'parcel')->all();

            foreach ($parcels as $parcel) {
                // convert all length units to cm and weight units to grams
                $length = $parcel->length_unit->toUnit('cm');
                $width = $parcel->width_unit->toUnit('cm');
                $height = $parcel->height_unit->toUnit('cm');
                $weight = $parcel->mass_unit->toUnit('g');
                $serviceParcelFee = null;

                // iterate through parcel fees to find where it fits
                foreach ($this->parcelFees as $parcelFee) {
                    $feeLength = $parcelFee->length_unit->toUnit('cm');
                    $feeWidth = $parcelFee->width_unit->toUnit('cm');
                    $feeHeight = $parcelFee->height_unit->toUnit('cm');
                    $feeWeight = $parcelFee->mass_unit->toUnit('g');

                    $previousParcelFee = $parcelFee;

                    if ($length > $feeLength && $width > $feeWidth && $height > $feeHeight && $weight > $feeWeight) {
                        continue;
                    } elseif ($length < $feeLength && $width < $feeWidth && $height < $feeHeight && $weight < $feeWeight) {
                        $serviceParcelFee = $previousParcelFee;
                    } else {
                        $serviceParcelFee = $parcelFee;
                    }
                }

                // if no distance fee use the last
                if ($serviceParcelFee === null) {
                    $serviceParcelFee = $this->parcelFees->sortByDesc()->first();
                }

                $subTotal += $serviceParcelFee->fee;

                $lines->push([
                    'details' => $serviceParcelFee->name . ' parcel fee',
                    'amount' => Utils::numbersOnly($serviceParcelFee->fee),
                    'formatted_amount' => Utils::moneyFormat($serviceParcelFee->fee, $this->currency),
                    'currency' => $this->currency,
                    'code' => 'PARCEL_FEE',
                ]);
            }
        }

        // set the base rate
        $baseRate = $subTotal;

        // if the rate has cod add this into the quote price
        if ($this->hasCodFee() && $payload->cod_amount !== null) {
            if ($this->hasCodFlatFee()) {
                $subTotal += $codFee = $this->cod_flat_fee;
            } elseif ($this->hasCodPercentageFee()) {
                $subTotal += $codFee = Utils::calculatePercentage($this->cod_percent, $baseRate);
            }

            $lines->push([
                'details' => 'Cash on delivery fee',
                'amount' => Utils::numbersOnly($codFee),
                'formatted_amount' => Utils::moneyFormat($codFee, $this->currency),
                'currency' => $this->currency,
                'code' => 'COD_FEE',
            ]);
        }

        // if this has peak hour fee add in
        if ($this->hasPeakHoursFee() && $this->isWithinPeakHours()) {
            if ($this->hasPeakHoursFlatFee()) {
                $subTotal += $peakHoursFee = $this->peak_hours_flat_fee;
            } elseif ($this->hasPeakHoursPercentageFee()) {
                $subTotal += $peakHoursFee = Utils::calculatePercentage($this->peak_hours_percent, $baseRate);
            }

            $lines->push([
                'details' => 'Peak hours fee',
                'amount' => Utils::numbersOnly($peakHoursFee),
                'formatted_amount' => Utils::moneyFormat($peakHoursFee, $this->currency),
                'currency' => $this->currency,
                'code' => 'PEAK_HOUR_FEE',
            ]);
        }

        return [$subTotal, $lines];
    }

    public function findServiceRateFeeByDistance(int $totalDistance): ?ServiceRateFee
    {
        $this->load('rateFees');

        $distanceInKms = round($totalDistance / 1000);
        $distanceFee = null;

        foreach ($this->rateFees as $rateFee) {
            $previousRateFee = $rateFee;

            if ($distanceInKms > $rateFee->distance) {
                continue;
            } elseif ($rateFee->distance > $distanceInKms) {
                $distanceFee = $previousRateFee;
            } else {
                $distanceFee = $rateFee;
            }
        }

        // if no distance fee use the last
        if ($distanceFee === null) {
            $distanceFee = $this->rateFees->sortByDesc('distance')->first();
        }

        return $distanceFee;
    }

    public function findServiceRateFeeByMinMax(int $number): ?ServiceRateFee
    {
        $this->load('rateFees');

        $serviceRateFee = null;

        foreach ($this->rateFees as $rateFee) {
            if ($rateFee->isWithinMinMax($number)) {
                $serviceRateFee = $rateFee;
                break;
            }
        }

        // if no distance fee use the last
        if ($serviceRateFee === null) {
            $serviceRateFee = $this->rateFees->sortByDesc('max')->first();
        }

        return $serviceRateFee;
    }
}
