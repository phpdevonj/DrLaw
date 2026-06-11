<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Coupon;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class EstimateServiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $distance_unit = optional($this->region)->distance_unit ?? 'km';
        $distance_in_unit = request('distance_in_unit');
        $dropoff_distance_in_meters = request('dropoff_distance_in_meters');
        $dropoff_time_in_seconds = request('dropoff_time_in_seconds');
        $coupon = request('coupon');
        $pick_lat = request('pick_lat');
        $pick_lng = request('pick_lng');
        $drop_lat = request('drop_lat');
        $drop_lng = request('drop_lng');
        $multi_location = request('multi_location', []);
        // get timezone
        $timezone = optional($this->region)->timezone ?? 'UTC';
        $date_time = \Carbon\Carbon::now()->setTimezone($timezone)->format('Y-m-d H:i');

        Log::channel('surge')->info('Surge check started', [
            'ride_datetime' => $date_time,
            'region_id'     => $this->region_id,
            'pickup'        => [$pick_lat, $pick_lng],
            'drop'          => [$drop_lat, $drop_lng],
        ]);
        $surge_price = getSurgePrice($date_time, $this->region_id, $pick_lat, $pick_lng, $drop_lat, $drop_lng);

        Log::channel('surge')->info('Surge price applied', [
            'surge_price' => is_object($surge_price)
                ? $surge_price->toArray()
                : $surge_price
        ]);
        
        $is_credit_used = request('is_credit_used');
        $rider_id = request('rider_id');
        
        $service_data = [
            'id'                => $this->id,
            'service_id'        => $this->id,
            'name'              => $this->name,
            'region_id'         => $this->region_id,
            'distance_unit'     => $distance_unit,
            'dropoff_distance_in_km' => round($dropoff_distance_in_meters/1000,2),
            'dropoff_distance_in_miles' => round(km_to_mile($dropoff_distance_in_meters/1000), 2),
            'duration'          => $dropoff_time_in_seconds/60,
            'estimate_time'     => convertSecondsToReadableTime($dropoff_time_in_seconds),
            'capacity'          => $this->capacity,
            'base_fare'         => $this->base_fare,
            'minimum_fare'      => $this->minimum_fare,
            'minimum_distance'  => $this->minimum_distance,
            'per_distance'      => $this->per_distance,
            'per_minute_drive'  => $this->per_minute_drive,
            'per_minute_wait'   => $this->per_minute_wait,
            'waiting_time_limit'=> $this->waiting_time_limit,
            'cancellation_fee'  => $this->cancellation_fee,
            // 'place_details'     => $place_details,
            'payment_method'    => $this->payment_method,            
            'service_image'     => getSingleMedia($this, 'service_image',null),
            'status'            => $this->status,
            'created_at'        => $this->created_at,
            'updated_at'        => $this->updated_at,
            'description'       => $this->description,
            'commission_type'   => $this->commission_type,
            'admin_commission'  => $this->admin_commission,
            'fleet_commission'  => $this->fleet_commission,
            'time_fare_short_ride'          => $this->time_fare_short_ride,
            'time_fare_moderate_ride'       => $this->time_fare_moderate_ride,
            'time_fare_long_ride'           => $this->time_fare_long_ride,
            'company_fee_threshold'         => $this->company_fee_threshold,
            'company_fee_below_threshold'   => $this->company_fee_below_threshold,
            'company_fee_above_threshold'   => $this->company_fee_above_threshold,
            'expenses'                      => $this->expenses,
        ];

        // caclulate ride
        $ridefee = calculateRideFares($distance_in_unit,$pick_lat, $pick_lng, $drop_lat, $drop_lng, $multi_location,$dropoff_time_in_seconds, $service_data, $coupon,$surge_price,$date_time,$is_credit_used,$rider_id);

        return array_merge($service_data, $ridefee);
    }
}