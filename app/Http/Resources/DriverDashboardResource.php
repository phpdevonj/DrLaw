<?php

namespace App\Http\Resources;

use App\Models\Coupon;
use App\Models\Service;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Sos;
use MatanYadaev\EloquentSpatial\Objects\Point;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
class DriverDashboardResource extends JsonResource
{
    public function toArray($request)
    {
        $ride_block_minutes = (int) SettingData('ride', 'normal_ride_restriction_buffer') ?? 30;
        $on_ride_request = $this->driverRideRequestDetail()
            ->whereNotIn('status', ['canceled'])
            ->where('is_driver_rated', false)
            ->where(function ($q) use ($ride_block_minutes) {
                // Normal ride
                $q->where('is_schedule', 0)

                // Accepted scheduled ride - bypass ride block minutes
                ->orWhere(function ($q2) {
                    $q2->where('is_schedule', 1)
                    ->whereIn('status', ['accepted', 'arriving', 'arrived', 'in_progress']);
                })

                // Other scheduled rides - apply ride block minutes
                ->orWhere(function ($q2) use ($ride_block_minutes) {
                    $q2->where('is_schedule', 1)
                    ->where('status', '!=', 'accepted')
                    ->where('scheduled_at', '<=', Carbon::now()->addMinutes($ride_block_minutes));
                });
            })
            ->first();
        
        //$pending_payment_ride_request = $this->driverRideRequestDetail()->where('status', 'completed')->where('is_driver_rated',true)
        $pending_payment_ride_request = $this->driverRideRequestDetail()->where('status', 'completed')
                        ->whereHas('payment',function ($q) {
                            $q->where('payment_status', 'pending');
                        })
                        ->first();
        $rider = isset($on_ride_request) && optional($on_ride_request->rider) ? $on_ride_request->rider :  null;
        $payment = isset($pending_payment_ride_request) && optional($pending_payment_ride_request->payment) ? $pending_payment_ride_request->payment : null;
        
        if (!empty($on_ride_request)) {
            $service = Service::where('id', $on_ride_request->service_id)->first();
            if ($service) {
                if ($service->region_id) {
                    $service = Service::where('region_id', $service->region_id)->where('id', $service->id)->first();
                }

                if ($on_ride_request->start_latitude && $on_ride_request->start_longitude) {
                    $point = new Point($on_ride_request->start_latitude, $on_ride_request->start_longitude);
                    $service = Service::whereHas('region', function ($q) use ($point) {
                        $q->where('status', 1)->whereContains('coordinates', $point);
                    })->where('id', $service->id)->first();
                }

                // if ($on_ride_request->coupon_code) {
                //     $on_ride_coupon_code = Coupon::where('id', $on_ride_request->coupon_code)->value('code');
                //     $response = verify_coupon_code($on_ride_coupon_code);

                //     if ($response['status'] != 200) {
                //         return json_custom_response($response, $response['status']);
                //     }
                // }

                if (!empty($on_ride_request->multi_drop_location)) {
                    $place_details = og_get_distance_matrix_multiple_destination(
                        $on_ride_request->start_latitude, 
                        $on_ride_request->start_longitude, 
                        $on_ride_request->end_latitude, 
                        $on_ride_request->end_longitude, 
                        $on_ride_request->multi_drop_location
                    );
                    $dropoff_distance_in_meters = $place_details['distance'];
                    $dropoff_time_in_seconds = $place_details['duration'];
                } else {
                    $place_details = og_get_distance_matrix(
                        $on_ride_request->start_latitude, 
                        $on_ride_request->start_longitude, 
                        $on_ride_request->end_latitude, 
                        $on_ride_request->end_longitude
                    );

                    $dropoff_distance_in_meters = distance_value_from_distance_matrix($place_details);
                    $dropoff_time_in_seconds = duration_value_from_distance_matrix($place_details);
                }

                $distance_in_unit = $dropoff_distance_in_meters ? $dropoff_distance_in_meters / 1000 : 0;

                // $status = $coupon_code ? 400 : 200;
                // if ($coupon) {
                //     $status = Coupon::isValidCoupon($coupon);
                // }
                
                // if ($status != 200) {
                //     $response = couponVerifyResponse($status);
                //     return json_custom_response($response, $status);
                // }

                $request['distance_in_unit'] = $distance_in_unit;
                $request['dropoff_distance_in_meters'] = $dropoff_distance_in_meters;
                $request['dropoff_time_in_seconds'] = $dropoff_time_in_seconds;
                $request['coupon'] = $on_ride_request->coupon_data;
                $request['pick_lat'] = $on_ride_request->start_latitude;
                $request['pick_lng'] = $on_ride_request->start_longitude;
                $request['drop_lat'] = $on_ride_request->end_latitude;
                $request['drop_lng'] = $on_ride_request->end_longitude;
                $request['multi_location'] = $on_ride_request->multi_drop_location;
                $request['datetime'] = $on_ride_request->datetime;
                $services = collect([$service]);
                $items = EstimateServiceResource::collection($services);
            }
        }

        return [
            'id'                => $this->id,
            'display_name'      => $this->display_name,
            'email'             => $this->email,
            'username'          => $this->username,
            'user_type'         => $this->user_type,
            'profile_image'     => getSingleMedia($this, 'profile_image',null),
            'status'            => $this->status,
            'multi_drop_location' => $this->multi_drop_location,
            'ride_has_bid'      => $this->driverRideRequestDetail()->latest()->first()?->ride_has_bid === 1 ? 1 : 0,
            'latitude'          => $this->latitude,
            'longitude'         => $this->longitude,
            // 'sos'               => Sos::mySOs()->get(),
            'on_ride_request'   => isset($on_ride_request) ? new RideRequestResource($on_ride_request) : null,
            'rider'             => isset($rider) ? new UserResource($rider) : null,
            'payment'           => isset($payment) ? new PaymentResource($payment) : null,
            'estimated_price'   => $items ?? [],
        ];
    }
}