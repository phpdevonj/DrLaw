<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RideRequest;
use App\Models\RideRequestRating;
use App\Models\Coupon;
use App\Http\Resources\RideRequestResource;
use App\Http\Resources\ComplaintResource;
use App\Http\Resources\EstimateServiceResource;
use Carbon\Carbon;
use App\Models\Payment;
use App\Jobs\NotifyViaMqtt;
use App\Models\RideRequestBid;
use App\Models\Service;
use App\Models\SurgePrice;
use MatanYadaev\EloquentSpatial\Objects\Point;
use Illuminate\Support\Facades\Http;
use Validator;
use App\Models\Point as RiderPoints;
use App\Models\PointHistory;
use Illuminate\Support\Facades\DB;
use App\Models\Wallet;
use App\Models\WalletHistory;
use App\Http\Resources\ScheduleRideRequestResource;

class RideRequestController extends Controller
{
    public function getList(Request $request)
    {
        $riderequest = RideRequest::query();

        $riderequest->when(request('service_id'), function ($q) {
            return $q->where('service_id', request('service_id'));
        });

        $riderequest->when(request('is_schedule'), function ($q) {
            return $q->where('is_schedule', request('is_schedule'));
        });

        $riderequest->when(request('rider_id'), function ($q) {
            return $q->where('rider_id',request('rider_id'));
        });

        $riderequest->when(request('driver_id'), function ($query) {
            return $query->whereHas('driver',function ($q) {
                $q->where('driver_id',request('driver_id'));
            });
        });
        $order = 'desc';
        $riderequest->when(request('status'), function ($query) {
            if( request('status') == 'upcoming' ) {
                return $query->where('scheduled_at', '>=', Carbon::now()->format('Y-m-d H:i:s'));
            }else if( request('status') == 'history' ) {
                //return $query->where('created_at', '<', Carbon::now()->format('Y-m-d H:i:s'));

                return $query->where(function ($q) {
                    $q->where('is_schedule', 0) // normal rides
                      ->orWhere(function ($sub) {
                          $sub->where('is_schedule', 1) // scheduled rides
                              ->whereIn('status', ['completed', 'canceled']);
                      });
                });
            }  else if( request('status') == 'canceled' ) {
                return $query->whereIn('status',['canceled']);
            } else {
                return $query->where('status', request('status'));
            }
        });

        if( request('from_date') != null && request('to_date') != null ){
            $riderequest = $riderequest->whereBetween('datetime',[ request('from_date'), request('to_date')]);
        }

        $per_page = config('constant.PER_PAGE_LIMIT');
        if( $request->has('per_page') && !empty($request->per_page)){
            if(is_numeric($request->per_page))
            {
                $per_page = $request->per_page;
            }
            if($request->per_page == -1 ){
                $per_page = $riderequest->count();
            }
        }
        if( request('status') == 'upcoming' ) {
            $order = 'asc';
        }
        $riderequest = $riderequest->orderBy('datetime',$order)->paginate($per_page);
        $items = RideRequestResource::collection($riderequest);

        $response = [
            'pagination' => json_pagination_response($items),
            'data' => $items,
        ];
        
        return json_custom_response($response);
    }

    public function getDetail(Request $request)
    {
        $id = $request->id;
        $riderequest = RideRequest::where('id',$id)->first();

        if( $riderequest == null )
        {
            return json_message_response( __('message.not_found_entry',['name' => __('message.riderequest') ]) );
        }
        $ride_detail = new RideRequestResource($riderequest);

        $ride_history = optional($riderequest)->rideRequestHistory;
        $rider_rating = optional($riderequest)->rideRequestRiderRating();
        $driver_rating = optional($riderequest)->rideRequestDriverRating();

        $current_user = auth()->user();
        if(count($current_user->unreadNotifications) > 0 ) {
            $current_user->unreadNotifications->where('data.id',$id)->markAsRead();
        }

        $complaint = null;
        if($current_user->hasRole('driver')) {
            $complaint = optional($riderequest)->rideRequestDriverComplaint();
        }

        if($current_user->hasRole('rider')) {
            $complaint = optional($riderequest)->rideRequestRiderComplaint();
        }

        $service = Service::where('id', $riderequest->service_id)->first();

        if ($service) {
            if ($service->region_id) {
                $service = Service::where('region_id', $service->region_id)->where('id', $service->id)->first();
            }

            if ($riderequest->start_latitude && $riderequest->start_longitude) {
                $point = new Point($riderequest->start_latitude, $riderequest->start_longitude);
                $service = Service::whereHas('region', function ($q) use ($point) {
                    $q->where('status', 1)->whereContains('coordinates', $point);
                })->where('id', $service->id)->first();
            }

            // if ($riderequest->coupon_code && !in_array($riderequest->status, ['completed', 'canceled'])) {
            //     $rider_coupon_code = Coupon::where('id', $riderequest->coupon_code)->value('code');
            //     $response = verify_coupon_code($rider_coupon_code);

            //     if ($response['status'] != 200) {
            //         return json_custom_response($response, $response['status']);
            //     }
            // }

            if (!empty($riderequest->multi_drop_location)) {
                $place_details = og_get_distance_matrix_multiple_destination(
                    $riderequest->start_latitude, 
                    $riderequest->start_longitude, 
                    $riderequest->end_latitude, 
                    $riderequest->end_longitude, 
                    $riderequest->multi_drop_location
                );
                $dropoff_distance_in_meters = $place_details['distance'];
                $dropoff_time_in_seconds = $place_details['duration'];
            } else {
                $place_details = og_get_distance_matrix(
                    $riderequest->start_latitude, 
                    $riderequest->start_longitude, 
                    $riderequest->end_latitude, 
                    $riderequest->end_longitude
                );

                $dropoff_distance_in_meters = distance_value_from_distance_matrix($place_details);
                $dropoff_time_in_seconds = duration_value_from_distance_matrix($place_details);
            }

            $distance_in_unit = $dropoff_distance_in_meters ? $dropoff_distance_in_meters / 1000 : 0;
            // if(!in_array($riderequest->status, ['completed', 'canceled'])){
            //     $coupon_code = $riderequest->coupon_code;
            //     $coupon = Coupon::where('id', $coupon_code)->first();

            //     $status = $coupon_code ? 400 : 200;
            //     if ($coupon) {
            //         $status = Coupon::isValidCoupon($coupon);
            //     }
                
            //     if ($status != 200) {
            //         $response = couponVerifyResponse($status);
            //         return json_custom_response($response, $status);
            //     }
            // }            

            $request['distance_in_unit'] = $distance_in_unit;
            $request['dropoff_distance_in_meters'] = $dropoff_distance_in_meters;
            $request['dropoff_time_in_seconds'] = $dropoff_time_in_seconds;
            $request['coupon'] = $coupon ?? null;


            $request['pick_lat'] = $riderequest->start_latitude;
            $request['pick_lng'] = $riderequest->start_longitude;
            $request['drop_lat'] = $riderequest->end_latitude;
            $request['drop_lng'] = $riderequest->end_longitude;
            $request['multi_location'] = $riderequest->multi_drop_location;

            $services = collect([$service]);
            $items = EstimateServiceResource::collection($services);
        
            $pdfUrl = null;
            if($ride_detail->status == 'completed' ){
                $pdfUrl = route('ride-invoice', ['id' => $ride_detail->id]);
            }

            $bid_data = RideRequestBid::where('ride_request_id',$id)->where('driver_id',$current_user->id)->first();
            $response = [
                'data' => $ride_detail,
                'ride_history' => $ride_history,
                'rider_rating' => $rider_rating,
                'driver_rating' => $driver_rating,
                'complaint' => isset($complaint) ? new ComplaintResource($complaint) : null,
                'payment' => optional($ride_detail)->payment,
                'invoice_url' => $pdfUrl,
                'invoice_name' => 'Ride_' . $ride_detail->id,
                'estimated_price' => $items,
                'ride_has_bids' => $riderequest->ride_has_bid == 1 ? 1 : 0,
                'bid_data' => $bid_data ?? [],
                // 'region' => optional($ride_detail)->service_data['region'] 
            ];
            return json_custom_response($response);
        }
    }

    public function completeRideRequest(Request $request)
    {
        $id = $request->id;
        $ride_request = RideRequest::where('id',$id)->first();
        // \Log::info('riderequest:'.json_encode($request->all()));
        if( $ride_request == null ) {
            return json_message_response( __('message.not_found_entry',['name' => __('message.riderequest') ]) );
        }

        if( $ride_request->status == 'completed' ) {
            return json_message_response( __('message.ride.completed'));
        }

        $ride_request->update([
            'end_latitude'  => $request->end_latitude,
            'end_longitude' => $request->end_longitude,
            'end_address'   => $request->end_address,
            'extra_charges' => $request->extra_charges,
            'extra_charges_amount'  => $request->extra_charges_amount
        ]);

        $distance_unit = $ride_request->distance_unit ?? 'km';
        $distance = $request->distance;

        if( $distance_unit == 'mile' ) {
            $distance = km_to_mile($distance);
        }
        $service = $ride_request->service;

        $start_datetime = $ride_request->rideRequestHistory()->where('history_type', 'in_progress')->pluck('datetime')->first();
        
        $duration = calculateRideDuration($start_datetime);

        $arrived_datetime = $ride_request->riderequest_history_data('arrived');

        $waiting_time = calculateRideDuration($start_datetime, $arrived_datetime);

        $waiting_time = $waiting_time - ($service->waiting_time_limit ?? 0);
        $waiting_time = $waiting_time < 0 ? 0 : $waiting_time;

        
        $ride_request->update([
            'status' => 'completed',
            'distance' => $distance,
            'duration' => $duration,
            'service_data' => $service,
        ]);

        $history_data = [
            'history_type'      => 'completed',
            'ride_request_id'   => $ride_request->id,
            'ride_request'      => $ride_request,
        ];

        $current_date = Carbon::today()->toDateTimeString();
        $coupon = Coupon::where('id', $ride_request->coupon_code)->where('start_date', '<=',$current_date)->where('end_date', '>=',$current_date)->first();
        $extra_charges_amount = $request->has('extra_charges_amount') ? request('extra_charges_amount') : 0;

        // get timezone
        $timezone = optional($service->region)->timezone ?? 'UTC';
        $date_time = \Carbon\Carbon::now()->setTimezone($timezone)->format('Y-m-d H:i');        
        $surge_price = getSurgePrice($date_time, $service->region_id, $ride_request->start_latitude, $ride_request->start_longitude, $ride_request->end_latitude, $ride_request->end_longitude);

        $ridefee = $this->calculateRideFares($service, $distance, $duration, $waiting_time, $extra_charges_amount, $coupon, $ride_request, $surge_price, $date_time);

        $ridefee['waiting_time_limit'] = $service->waiting_time_limit;
        $ridefee['per_minute_drive'] = $service->per_minute_drive;
        $ridefee['per_minute_waiting'] = $service->per_minute_wait;
        if( $ride_request->is_ride_for_other == 1 ) {
            $ridefee['is_rider_rated'] = true;
        }

        // early ride complete
        if(!empty($request->early_completion_reason)){
            $ridefee['completed_early'] = 1;
            $ridefee['early_completion_reason'] = $request->early_completion_reason ?? '';
            $ridefee['remaining_distance'] = $request->remaining_distance ?? 0;
        }
        

        $ride_request->update($ridefee);

        // $ride_datetime = $ride_request->datetime;
        // $surge_price = $this->getSurgePrice($ride_datetime);

        // if (isset($surge_price) && !empty($surge_price)) {
        //     if ($surge_price->type == 'fixed') {
        //         $surge_amount = $surge_price->value;
        //     } elseif ($surge_price->type == 'multiply') {
        //         $surge_amount = ($ridefee['total_amount'] * $surge_price->value) / 100;
        //     }
        //     $ridefee['total_amount'] += $surge_amount;
        // }

        $ride_request->update($ridefee);
        
        $payment_data = [
            'rider_id'          => $ride_request->rider_id,
            'ride_request_id'   => $ride_request->id,
            'payment_type'      => $ride_request->payment_type ?? 'cash',
            'datetime'          => date('Y-m-d H:i:s'),
            'payment_status'    => 'pending',
            'total_amount'      => $ridefee['total_amount'],
            'credit_used'       => $ride_request->credit_used,
        ];
        if ($ride_request->ride_has_bid == 1) {
            $ride_bid_data = $ride_request->bids()->where('is_bid_accept',1)->first();
            $payment_data = [
                'rider_id'          => $ride_request->rider_id,
                'ride_request_id'   => $ride_request->id,
                'payment_type'      => $ride_request->payment_type ?? 'cash',
                'datetime'          => date('Y-m-d H:i:s'),
                'payment_status'    => 'pending',
                'total_amount'      => $ride_bid_data->bid_amount,
                'credit_used'       => $ride_request->credit_used,
            ];
        } else {
            $payment_data = [
                'rider_id'          => $ride_request->rider_id,
                'ride_request_id'   => $ride_request->id,
                'payment_type'      => $ride_request->payment_type ?? 'cash',
                'datetime'          => date('Y-m-d H:i:s'),
                'payment_status'    => 'pending',
                'total_amount'      => $ridefee['total_amount'],
                'credit_used'       => $ride_request->credit_used,
            ];
        }

        Payment::create($payment_data);

        // deduct use credits from wallet and create wallet history
        if(!empty($ride_request->credit_used) && $ride_request->credit_used > 0){
            $user_wallet = Wallet::firstOrCreate([ 'user_id' => $ride_request->rider_id ]);
            $total_wallet_amount = $user_wallet->total_amount - $ride_request->credit_used;   
            $user_wallet->total_amount = $total_wallet_amount;
            $user_wallet->save();

            $wallet_history['user_id'] = $ride_request->rider_id;                   
            $wallet_history['datetime'] = date('Y-m-d H:i:s');
            $wallet_history['type'] = 'debit';
            $wallet_history['transaction_type'] = 'credit_used';
            $wallet_history['balance'] = $total_wallet_amount;
            $wallet_history['amount'] = $ride_request->credit_used;
            $walletResult = WalletHistory::create($wallet_history); 
        }

        saveRideHistory($history_data);
        // update driver is_available
        $ride_request->driver->update(['is_available' => 1]);

        $loyalty_program = SettingData('ride', 'loyalty_program') ?? 0;
        if($loyalty_program){
            // transfer loyalty points to rider account
            $this->transferLoyaltyPointToRiderAccount($ridefee['subtotal'], $ride_request->rider_id, $ride_request->id);
        }

        // Add referral reward logic
        $referral_condition = SettingData('referral', 'referral_reward_condition') ?? null;
        if(isset($referral_condition) && $referral_condition == "on_first_ride_complete"){
            $rider = $ride_request->rider;
            if(isset($rider->referred_by) && !empty($rider->referred_by)){
                $riderCompletedRides = RideRequest::where('rider_id', $rider->id)
                ->where('status', 'completed')
                ->count();

                if ($riderCompletedRides === 1) {
                    // award referral bonus if applicable
                    processReferral($rider->referred_by, $rider->id);
                }
            }            
        }  
        

        return json_message_response( __('message.ride.completed'));
    }

    public function calculateRideFares($service, $distance, $duration, $waiting_time, $extra_charges_amount, $coupon, $riderequest, $surge_price, $ride_time)
    {
        // distance price
        $per_minute_drive_charge = 0;

        $per_minute_drive_charge = $duration * $service->per_minute_drive;
        if( $distance > $service->minimum_distance ) {
            $distance = $distance - $service->minimum_distance;
        }else{
            // If the distance is less than the minimum distance, we keep the distance as 0
            // because the base fare already covers up to the minimum distance.
            $distance = 0;
        }
        $per_distance_charge = $distance * $service->per_distance; // Distance Fare

        // Time Fare
        if ($duration <= $distance) {
            // Short duration ride
            $per_minute_time_fare_charge = $duration * $service->time_fare_short_ride;
        } elseif ($duration > $distance && $duration <= 2 * $distance) {
            // Moderate duration ride
            $per_minute_time_fare_charge = $duration * $service->time_fare_moderate_ride;
        } elseif ($duration > 2 * $distance) {
            // Long duration / heavy traffic
            $per_minute_time_fare_charge = $duration * $service->time_fare_long_ride;
        } else {
            $per_minute_time_fare_charge = 0;
        }

        $per_minute_waiting_charge = $waiting_time * $service->per_minute_wait; // Time Idling
              
        $base_fare = $service->base_fare; // Base Fare
        $minimum_fare = $service->minimum_fare; // Minimum Fare

        $total_amount = $base_fare + $per_distance_charge + $per_minute_time_fare_charge + $per_minute_waiting_charge; // Total ride fare

        // Company fee applicable on total ride fare
        if($total_amount < $service->company_fee_threshold){
            $company_fee = $service->company_fee_below_threshold;
        }else{
            $company_fee = $service->company_fee_above_threshold;
        }

        $total_amount += $company_fee; // Normal Ride Fare

        // Expenses
        $expenses = $total_amount * $service->expenses/100;

        // Total Ride Fee (what rider pays)
        $total_amount += $expenses;


        if( $total_amount < $service->minimum_fare ){
            $total_amount = $service->minimum_fare;
        } else {
            $minimum_fare = 0;
        }
        $total_amount += $extra_charges_amount; // Additional fees
        
        // if( $service->commission_type == 'fixed' ) {
        //     $commission = $service->admin_commission + $service->fleet_commission;
        //     if( $total_amount <= $commission) {
        //         $total_amount += $commission;
        //     }
        // }
        $subtotal = $total_amount;

        // Check for coupon data
        $discount_amount = 0;
        if ($coupon) {
            if ($coupon->minimum_amount < $total_amount) {
                
                if( $coupon->discount_type == 'percentage' ) {
                    $discount_amount = $total_amount * ($coupon->discount/100);
                } else {
                    $discount_amount = $coupon->discount;
                }

                if ($coupon->maximum_discount > 0 && $discount_amount > $coupon->maximum_discount) {
                    $discount_amount = $coupon->maximum_discount;
                }
                $subtotal = $total_amount - $discount_amount;
            }
        }

        if(!empty($riderequest->credit_used) && $riderequest->credit_used > 0){
            $subtotal = $subtotal - $riderequest->credit_used;
        }

        $surge_type   = null;
        $surge_value = null;
        $surge_amount = 0;
        $surge_price_setting_value = SettingData('ride', 'surge_price') ?? null;
        if ($surge_price_setting_value == 1 && isset($surge_price) && (is_object($surge_price) || is_array($surge_price))) {  
            
            $timezone = $service->region->timezone ?? 'UTC';
            $rideTimeOnly = \Carbon\Carbon::createFromFormat('Y-m-d H:i', $ride_time, $timezone)->format('H:i');

            foreach ($surge_price->from_time as $index => $from_time) {
                $to_time = $surge_price->to_time[$index];
        
                // Handle normal & overnight surge windows
                if ($from_time <= $to_time) {
                    // Same-day surge (e.g. 13:00 - 23:59)
                    $inRange = $rideTimeOnly >= $from_time && $rideTimeOnly <= $to_time;
                } else {
                    // Overnight surge (e.g. 22:00 - 06:00)
                    $inRange = $rideTimeOnly >= $from_time || $rideTimeOnly <= $to_time;
                }
        
                if ($inRange) {
                    if ($surge_price->type === 'fixed') {
                        $surge_amount = (float) $surge_price->value;
                    } elseif ($surge_price->type === 'percentage') {
                        $surge_amount = ($subtotal * $surge_price->value) / 100;
                    }

                    $surge_type  = $surge_price->type;
                    $surge_value = (float) $surge_price->value;
        
                    $total_amount += $surge_amount;
                    break;
                }
            }
        }

        $subtotal = $subtotal + ($surge_amount ? $surge_amount : 0);

        return [
            'base_fare'                 => $base_fare,
            'minimum_fare'              => $minimum_fare,
            'base_distance'             => $service->minimum_distance,
            'per_distance'              => $service->per_distance,
            'per_distance_charge'       => (float) number_format( (float) $per_distance_charge, 2,'.',''), // Distance Fare
            'per_minute_drive_charge'   => (float) number_format( (float) $per_minute_drive_charge, 2,'.',''),
            'waiting_time'              => $waiting_time,
            'per_minute_waiting_charge' => $per_minute_waiting_charge, // Time Idling
            'subtotal'                  => (float) number_format( (float) $subtotal, 2,'.',''),
            'total_amount'              => (float) number_format( (float) $total_amount, 2,'.',''),
            'extra_charges_amount'      => $extra_charges_amount,
            'coupon_discount'           => $discount_amount,
            'time_fare_short_ride'      => $service->time_fare_short_ride,
            'time_fare_moderate_ride'   => $service->time_fare_moderate_ride,
            'time_fare_long_ride'       => $service->time_fare_long_ride,
            'per_minute_time_fare_charge' => $per_minute_time_fare_charge, // Time fare  
            'company_fee_threshold'       => $service->company_fee_threshold,
            'company_fee_below_threshold' => $service->company_fee_below_threshold,
            'company_fee_above_threshold' => $service->company_fee_above_threshold,
            'company_fee_charge'          => $company_fee, // Company fee
            'expenses'                    => $service->expenses,
            'expenses_charge'             => (float) number_format( (float) $expenses, 2,'.',''), // Expenses
            'surge_type'                  => $surge_type,
            'surge_value'                 => $surge_value,
            'surge_amount'                => (float) $surge_amount,
        ];
    }

    public function verifyCoupon(Request $request)
    {
        $coupon_code = $request->coupon_code;

        $coupon = Coupon::where('code', $coupon_code)->first();
        $status = isset($coupon_code) ? 400 : 200;
        
        if($coupon != null) {
            $status = Coupon::isValidCoupon($coupon);
        }
        
        $response = couponVerifyResponse($status);

        return json_custom_response($response,$status);
    }

    public function rideRating(Request $request)
    {
        DB::beginTransaction();
        try {
            $ride_request = RideRequest::find($request->ride_request_id);

            if (!$ride_request) {
                return json_message_response(__('message.not_found_entry', ['name' => __('message.riderequest')]));
            }

            $user = auth()->user();
            $data = $request->all();

            // Assign rider_id or driver_id based on who rated
            $data['rider_id'] = auth()->user()->user_type == 'driver' ? $ride_request->rider_id : null;
            $data['driver_id'] = auth()->user()->user_type == 'rider' ? $ride_request->driver_id : null;
            $data['rating_by'] = $user->user_type;

            // Store or update rating
            RideRequestRating::updateOrCreate([ 'id' => $request->id ], $data);
            
            // Update rating flags
            if(auth()->user()->hasRole('rider')) {
                $ride_request->update(['is_rider_rated' => true]);
                $msg = __('message.rated_successfully', ['form' => __('message.rider')]);
            }elseif ($user->hasRole('driver')) {
                $ride_request->update(['is_driver_rated' => true]);
                $msg = __('message.rated_successfully', ['form' => __('message.driver')]);
            }

            // Update Firestore if required
            if( auth()->user()->hasRole('driver') && $ride_request->status !== 'completed' && $ride_request->payment->payment_status != 'paid') {
                $this->updateFirestoreRideDocument($ride_request, 'driver');
                // dispatch(new NotifyViaMqtt('ride_request_status_'.$ride_request->rider_id, json_encode($notify_data)));
            }

            // fixed ride issue on driver
            // if( auth()->user()->hasRole('rider') && $ride_request->status !== 'completed' && $ride_request->payment->payment_status != 'paid') {
            //     $this->updateFirestoreRideDocument($ride_request, 'rider');
            //     // dispatch(new NotifyViaMqtt('ride_request_status_'.$ride_request->driver_id, json_encode($notify_data)));
            // }

            // Handle tip payment if provided
            if ($request->filled('tips')) {
                $this->processTipPayment($request, $ride_request);
            }

            DB::commit();

            return json_message_response(__('message.save_form', ['form' => __('message.rating')]));
        } catch (\Exception $e) {
            DB::rollBack();
    
            // Log the error for debugging
            \Log::error('Ride Rating Failed: ' . $e->getMessage(), [
                'ride_request_id' => $request->ride_request_id,
                'user_id' => auth()->id(),
            ]);
    
            return json_message_response(__('message.something_wrong') . ' - ' . $e->getMessage(), 500);
        }
    }

    public function placeAutoComplete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'search_text' => 'required',
            'language' => 'required'
        ]);

        if ( $validator->fails() ) {
            $data = [
                'status' => 'false',
                'message' => $validator->errors()->first(),
                'all_message' =>  $validator->errors()
            ];

            return json_custom_response($data,400);
        }
        
        $google_map_api_key = env('GOOGLE_MAP_KEY');
        
        $response = Http::withHeaders([
            'Accept-Language' => request('language'),
        ])->get('https://maps.googleapis.com/maps/api/place/autocomplete/json?input='.request('search_text').'&key='.$google_map_api_key);

        return $response->json();
    }

    public function placeDetail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'placeid' => 'required',
        ]);

        if ( $validator->fails() ) {
            $data = [
                'status' => 'false',
                'message' => $validator->errors()->first(),
                'all_message' =>  $validator->errors()
            ];

            return json_custom_response($data,400);
        }
        
        $google_map_api_key = env('GOOGLE_MAP_KEY');
        $response = Http::get('https://maps.googleapis.com/maps/api/place/details/json?placeid='.$request->placeid.'&key='.$google_map_api_key);

        return $response->json();
    }

    public function getSurgePrice($ride_datetime) {
        if ($ride_datetime === null) {
            return null;
        }
        $ride_datetime = Carbon::parse($ride_datetime);
        $day = $ride_datetime->format('l');
        $current_time = $ride_datetime->format('H:i');
        
        $surge_prices = SurgePrice::where('day', $day)->get();
    
        foreach ($surge_prices as $surge) {
            $from_times = $surge->from_time;
            $to_times = $surge->to_time;
    
            foreach ($from_times as $index => $from_time) {
                $to_time = $to_times[$index];
    
                if (strtotime($current_time) >= strtotime($from_time) && strtotime($current_time) <= strtotime($to_time)) {
                    return $surge;
                }
            }
        }
    
        return "";
    }

    public function updateFirestoreRideDocument($ride_request, $role)
    {
        try {
            $document_name = 'ride_' . $ride_request->id;
            $firebaseData = app('firebase.firestore')->database()->collection('rides')->document($document_name);
            
            if ($firebaseData) {
                $rideData = [
                    'driver_ids' => [$ride_request->driver_id],
                    'on_rider_stream_api_call' => 1,
                    'on_stream_api_call' => 1,
                    'ride_id' => $ride_request->id,
                    'rider_id' => $ride_request->rider_id,
                    'status' => $ride_request->status,
                    'payment_status' => $ride_request->payment_status,
                    'payment_type' => $ride_request->payment_type,
                    'tips' => $ride_request->tips ? 1 : 0,
                ];
        
                $firebaseData->set($rideData, ['merge' => true]);
            } else {
                \Log::info('Document does not exist: ' . $document_name);
                return null;
            }
        } catch (\Exception $e) {
            \Log::error('Error updating Firestore document for Ride: ' . $e->getMessage());
        }
    }

    public function transferLoyaltyPointToRiderAccount($subtotal, $userId, $ride_request_id){
        if(empty($subtotal)){
            return json_custom_response('Ride amount is required.');
        }
        $point_ratio = SettingData('ride', 'point_ratio') ?? 0;
        $point_value = SettingData('ride', 'point_value') ?? 0;
        $points = ($subtotal * ($point_ratio/100))*$point_value;

        $point_data = RiderPoints::firstOrCreate(['user_id'=>$userId]);

        $total_points = $point_data->total_points + $points;

        $point_data->total_points  = $total_points;

        try{
            DB::beginTransaction();
            $point_data->save();
            $point_history = [
                'user_id' => $userId,
                'ride_request_id' => $ride_request_id ?? null,
                'type' => 'credit',
                'transaction_type' => 'earned',
                'amount' => $points,
                'balance' => $total_points,
                'datetime' => date('Y-m-d H:i:s')
            ];
            $result = PointHistory::create($point_history);
            DB::commit();
            return true;
        }catch(\Exception $e){
            DB::rollback();
            return json_custom_response($e);
        }
    }

    /**
     * Get scheduled rides list
     */
    public function getScheduleRidesList(Request $request){
        $schedule_rides = RideRequest::whereIn('status', ['scheduled','driver_accepted'])->where('is_schedule', 1)->where('scheduled_at', '>=', Carbon::now()->format('Y-m-d H:i:s'));

        $schedule_rides->when(request('service_id'), function ($q) {
            return $q->where('service_id', request('service_id'));
        });
        
        $schedule_rides->when(request('rider_id'), function ($q) {
            return $q->where('rider_id',request('rider_id'));
        });

        // Driver filter (scheduled → all drivers, driver_accepted → only that driver, exclude cancelled drivers)
        $schedule_rides->when(request('driver_id'), function ($q) {
            $driverId = (int) request('driver_id');
            $now = Carbon::now();
        
            $q->where(function ($query) use ($driverId, $now) {
                // Case 1: Scheduled rides (apply 30-min & 5-hour rule, exclude cancelled drivers)
                $query->where(function ($sub) use ($driverId, $now) {
                    $sub->where('status', 'scheduled')
                        ->where(function ($time) use ($now) {
                            // Allow rides after 30 minutes from now (future rides only)
                            $time->where('scheduled_at', '>', $now->addMinutes(30));
                        })
                        ->where(function ($cancel) use ($driverId) {
                            $cancel->whereNull('cancelled_driver_ids')
                                   ->orWhereRaw("JSON_CONTAINS(cancelled_driver_ids, '[$driverId]') = 0");
                        });
                })
                // Case 2: Driver already accepted this ride (always show)
                ->orWhere(function ($sub) use ($driverId) {
                    $sub->where('status', 'driver_accepted')
                        ->where('driver_id', $driverId);
                });
            });
        });

        $order = 'desc';
        $per_page = config('constant.PER_PAGE_LIMIT');
        if( $request->has('per_page') && !empty($request->per_page)){
            if(is_numeric($request->per_page))
            {
                $per_page = $request->per_page;
            }
            if($request->per_page == -1 ){
                $per_page = $schedule_rides->count();
            }
        }

        $schedule_rides = $schedule_rides->orderBy('datetime',$order)->paginate($per_page);
        $items = ScheduleRideRequestResource::collection($schedule_rides);

        $response = [
            'pagination' => json_pagination_response($items),
            'data' => $items,
        ];

        return json_custom_response($response);
    }

    /**
     * Handle Tip Payment Logic
     */
    private function processTipPayment(Request $request, $ride_request)
    {
        $currency_code = SettingData('CURRENCY', 'CURRENCY_CODE') ?? 'USD';
        $currency_data = currencyArray($currency_code);
        $currency = strtolower($currency_data['code']);
        $amount = $request->tips;

        if ($request->payment_type === 'card' && hasStripeKeys()) {
            // Try capturing Stripe payment
            
            $captureRes = captureStripePaymentIntent($request->payment_intent_id, $amount * 100);

            if (!$captureRes['success']) {
                throw new \Exception("Stripe capture failed: " . json_encode($captureRes['error'] ?? []));
            }

            // Credit driver wallet
            creditDriverWallet($ride_request->driver_id, $amount, $currency, $ride_request->id);

            // Record tip
            recordRideTip($ride_request->id, $amount, 'card', 'completed', 'driver', $request->payment_intent_id);
        } else {
            // Debit rider wallet (no Stripe or non-card payment)
            debitRiderWallet($ride_request->rider_id, $amount, $currency, $ride_request->id);

            // Credit driver wallet
            creditDriverWallet($ride_request->driver_id, $amount, $currency, $ride_request->id);

            // Record tip
            recordRideTip($ride_request->id, $amount, 'wallet', 'completed', 'driver');
        }
    }
}
