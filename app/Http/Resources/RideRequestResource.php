<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RideRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $pdfUrl = null;
        if($this->status == 'completed' ){
            $pdfUrl = route('ride-invoice', ['id' => $this->id, 'user_type'=>auth()->user()->user_type]);
        }
        $surge_price = getSurgePrice($this->datetime, optional($this->service)->region_id, $this->start_latitude, $this->start_longitude, $this->end_latitude, $this->end_longitude);

        $getBidAmount = $this->approvedBids()->first();

        $driver_ratings = optional($this->driver)->driverRating ?? collect();
        $rider_ratings = optional($this->rider)->riderRating ?? collect();


        $driver_earning = $this->total_amount - $this->company_fee_charge - $this->expenses_charge;

        return [
            'id'                => $this->id,
            'rider_id'          => $this->rider_id,
            'service_id'        => $this->service_id,
            'datetime'          => $this->datetime,
            'is_schedule'       => $this->is_schedule,
            'ride_attempt'      => $this->ride_attempt,
            'otp'               => $this->otp,
            'total_amount'      => (float) number_format( (float) $this->total_amount, 2,'.',''),
            'subtotal'          => (!empty($getBidAmount) && $this->ride_has_bid == 1) ? $getBidAmount->bid_amount : (float) number_format( (float) $this->subtotal, 2,'.',''),
            'extra_charges_amount'  => $this->extra_charges_amount,
            'driver_id'         => $this->driver_id,
            'driver_name'       => optional($this->driver)->display_name,
            'rider_name'        => optional($this->rider)->display_name,
            'driver_email'       => optional($this->driver)->email,
            'rider_email'        => optional($this->rider)->email,
            'driver_contact_number' => optional($this->driver)->contact_number,
            'rider_contact_number'  => optional($this->rider)->contact_number,
            'driver_profile_image' => getSingleMedia(optional($this->driver), 'profile_image',null),
            'rider_profile_image' => getSingleMedia(optional($this->rider), 'profile_image',null),
            'start_latitude'    => $this->start_latitude,
            'start_longitude'   => $this->start_longitude,
            'start_address'     => $this->start_address,
            'end_latitude'      => $this->end_latitude,
            'end_longitude'     => $this->end_longitude,
            'end_address'       => $this->end_address,
            'distance_unit'     => $this->distance_unit,
            'start_time'        => $this->rideRequestStartTime() ?? null,
            'end_time'          => $this->rideRequestCompletedTime() ?? null,
            'riderequest_in_driver_id' => $this->riderequest_in_driver_id,
            'distance'          => (float) number_format( (float) $this->distance, 2,'.',''),
            'duration'          => $this->duration,
            'seat_count'        => $this->seat_count,
            'reason'            => $this->reason,
            'status'            => $this->status,
            'tips'              => $this->rideTip->tip_amount ?? 0,
            'base_fare'         => $this->base_fare,
            'minimum_fare'      => $this->minimum_fare,
            'per_distance'      => $this->per_distance,
            'per_distance_charge' => (float) number_format( (float) $this->per_distance_charge, 2,'.',''),
            'per_minute_drive'  => $this->per_minute_drive,
            'per_minute_drive_charge' => (float) number_format( (float) $this->per_minute_drive_charge, 2,'.',''),
            'per_minute_waiting'=> $this->per_minute_waiting,
            'waiting_time'      => $this->waiting_time,
            'waiting_time_limit'    => $this->waiting_time_limit,
            'per_minute_waiting_charge'  => $this->per_minute_waiting_charge,
            'cancelation_charges'   => $this->cancelation_charges,
            'cancel_by'         => $this->cancel_by,
            'payment_id'        => optional($this->payment)->id,
            'payment_type'      => $this->payment_type,
            'payment_status'    => optional($this->payment)->payment_status ?? 'pending',
            'extra_charges'     => $this->extra_charges,
            'surge_price_type'  => $this->surge_type ?? '',
            'surge_price_value' => (float) number_format( (float) $this->surge_value, 2,'.','') ?? 0,
            'fixed_charge'      => (float) number_format( (float) $this->surge_amount, 2,'.','') ?? 0,
            'coupon_discount'   => $this->coupon_discount,
            'coupon_code'       => $this->coupon_code,
            'coupon_data'       => $this->coupon_data,
            'credit_used'       => $this->credit_used,
            'is_rider_rated'    => $this->is_rider_rated,
            'is_driver_rated'   => $this->is_driver_rated,
            'max_time_for_find_driver_for_ride_request' => $this->max_time_for_find_driver_for_ride_request,
            'created_at'        => $this->created_at,
            'updated_at'        => $this->updated_at,
            'region_id'         => optional($this->service)->region_id,
            'is_ride_for_other' => $this->is_ride_for_other,
            'other_rider_data'  => $this->other_rider_data ?? null,
            'drop_location'     => $this->drop_location,
            'multi_drop_location'     => json_decode($this->multi_drop_location),
            'invoice_url' => $pdfUrl,
            'invoice_name' => 'Ride_' . $this->id,
            'driver_rating' => $driver_ratings->count() > 0 ? (float) number_format(max($driver_ratings->avg('rating'), 0), 2) : 0,
            'rider_rating'  => $rider_ratings->count() > 0 ? (float) number_format(max($rider_ratings->avg('rating'), 0), 2) : 0,
            'held_payment_intent_id'     => $this->held_payment_intent_id,
            'held_payment_amount'        => $this->held_payment_amount,
            'captured_payment_intent_id' => $this->captured_payment_intent_id,
            'rider_complete_trips'    => optional($this->rider)->completedTripsAsRiderCount(),
            'driver_complete_trips'    => optional($this->driver)->completedTripsAsDriverCount(),
            'expenses_charge'          => $this->expenses_charge,
            'company_fee_charge'       => $this->company_fee_charge,
            'driver_earning'       => (float) number_format( (float) $driver_earning, 2,'.',''),
            'service_cancellation_fee' => (float) number_format( (float) optional($this->service)->cancellation_fee, 2,'.',''),
        ];
    }
}