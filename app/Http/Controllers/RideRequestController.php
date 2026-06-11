<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RideRequest;
use App\DataTables\RideRequestDataTable;
use App\Http\Requests\RideRequestRequest;
use App\Models\Coupon;
use App\Models\Payment;
use App\Models\Service;
use Illuminate\Support\Facades\DB;
use App\Traits\PaymentTrait;
use App\Traits\RideRequestTrait;
use App\Jobs\NotifyViaMqtt;
use App\Http\Resources\RideRequestResource;
use App\Models\AppSetting;
use App\Models\Notification;
use App\Models\RideRequestBid;
use App\Models\Setting;
use App\Models\SurgePrice;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use App\Models\User;
use App\Notifications\CommonNotification;
use App\Models\Wallet;
use App\Models\WalletHistory;
use App\Models\RideRequestHistory;

class RideRequestController extends Controller
{
    use PaymentTrait, RideRequestTrait;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(RideRequestDataTable $dataTable)
    {
        $pageTitle = __('message.list_form_title',['form' => __('message.riderequest')] );
        $auth_user = authSession();
        $assets = ['datatable'];
        // $button = $auth_user->can('dispatch add') ? '<a href="'.route('dispatch.create').'" class="float-right btn btn-md border-radius-10 btn-outline-dark"><i class="fa fa-plus-circle"></i> '.__('message.book_now').'</a>' : '';
        $button = '';
        $rideRequestfilterButton = true;

        return $dataTable->render('global.datatable', compact('pageTitle', 'auth_user','button', 'rideRequestfilterButton'));
        
        // return $dataTable->render('global.datatable', compact('pageTitle','button','auth_user'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $pageTitle = __('message.add_form_title',[ 'form' => __('message.riderequest')]);
        
        return view('riderequest.form', compact('pageTitle'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
         // Rider should not be able to book a normal ride within X minutes of a scheduled ride.
        //  $normal_ride_restriction_buffer = SettingData('ride', 'normal_ride_restriction_buffer') ?? 0;
        //  if ($normal_ride_restriction_buffer > 0) {
        //      $utcNow = Carbon::now('UTC');
         
        //      $hasUpcomingScheduledRide = RideRequest::where([
        //              'rider_id' => request('rider_id'),
        //              'is_schedule' => 1,
        //          ])
        //          ->whereNotIn('status', ['canceled', 'completed']) 
        //          ->whereBetween('scheduled_at', [
        //              $utcNow,
        //              $utcNow->copy()->addMinutes($normal_ride_restriction_buffer)
        //          ])
        //          ->exists();
         
        //      if ($hasUpcomingScheduledRide) {
        //          $message = __('message.rider_normal_ride_restriction', [
        //              'name' => __('message.riderequest'),
        //              'minutes' => $normal_ride_restriction_buffer
        //          ]);
        //          return json_message_response($message, 400);
        //      }
        //  }
        $blockedScheduleStatuses = ['accepted','arriving','arrived','in progress'];
        $rider_has_blocking_scheduled_ride = RideRequest::where('rider_id', auth()->user()->id)
            ->where('is_schedule', 1) // check only scheduled ride
            ->whereIn('status', $blockedScheduleStatuses)
            ->exists();

        if ($rider_has_blocking_scheduled_ride) {
            return json_message_response(__('message.scheduled_ride_active_not_allowed'),400);
        }
         
        $data = $request->all();

        // Check if the rider has registred a riderequest already
        $rider_exists_riderequest = RideRequest::whereNotIn('status', ['canceled', 'completed'])->where('rider_id', auth()->user()->id)->where('is_schedule', 0)->exists();
        
        if($rider_exists_riderequest) {
            return json_message_response(__('message.rider_already_in_riderequest'), 400);
        }
        
        $coupon_code = $request->coupon_code;

        if( $coupon_code != null ) {
            $coupon = Coupon::where('code', $coupon_code)->first();
            $status = isset($coupon_code) ? 400 : 200;
        
            if($coupon != null) {
                $status = Coupon::isValidCoupon($coupon);
            }
            if( $status != 200 ) {
                $response = couponVerifyResponse($status);
                return json_custom_response($response,$status);
            } else {
                $data['coupon_code'] = $coupon->id;
                $data['coupon_data'] = $coupon;
            }
        }

        $service = Service::with('region')->where('id',$request->service_id)->first();
        $data['distance_unit'] = $service->region->distance_unit ?? 'km';
        if (isset($data['multi_location'])) {
            $data['multi_drop_location'] = json_encode($data['multi_location']);
        }
        $data['ride_has_bid'] = $request->ride_type == 'with_bidding' ? 1 : 0;

        $data['destination_latitude'] = $request->end_latitude;
        $data['destination_longitude'] = $request->end_longitude;
        $data['destination_address'] = $request->end_address;
        $result = RideRequest::create($data);

        $message = __('message.save_form', ['form' => __('message.riderequest')]);        
        
        $history_data = [
            'ride_request_id' => $result->id,
            'history_type'    => $result->status,
            'ride_request'    => $result,
            'driver_ids'      => $result,
        ];
    
        if ($request->ride_type == 'with_bidding') {
            if ($result->status == 'new_ride_requested') {
                $this->findDrivers($result);
            }
        } else {
            if ($result->status === 'new_ride_requested') {
                $this->acceptDeclinedRideRequest($result, $request->all());
            }
        }
        saveRideHistory($history_data);
        
        if($request->is('api/*')) {
            $response = [
                'riderequest_id' => $result->id,
                'message' => $message
            ];
            return json_custom_response($response);
		}

        return redirect()->route('riderequest.index')->withSuccess($message);
    }

    public function applyBidRideRequest(Request $request)
    {
        $auth_user = auth()->user();
        $driverID = $auth_user->id;

        $rideRequest = RideRequest::find($request->ride_request_id);
        
        if (!$rideRequest) {
            return json_message_response(__('message.ride_request_not_found', ['id' => $request->ride_request_id]), 404);
        }

        $existingBid = RideRequestBid::where('ride_request_id', $request->ride_request_id)
            ->where('driver_id', $driverID)
            ->first();

        if ($existingBid) {
            return json_message_response(__('message.already_bid_applied', ['id' => $request->ride_request_id, 'driver_name' => $auth_user->username]), 400);
        }

        RideRequestBid::create([
            'ride_request_id' => $request->ride_request_id,
            'is_bid_accept' => 0,
            'driver_id' => $driverID,
            'bid_amount' => $request->bid_amount,
            'notes' => $request->notes,
        ]);

        // $foundRideRequest = $this->findDrivers($rideRequest,$request->all());
        // $driver_ids = $foundRideRequest->driver_ids ?? [];

        $history_data = [
            'history_type' => 'bid_placed',
            'ride_request_id' => $rideRequest->id,
            'ride_request' => $rideRequest,
            // 'driver_ids' => $driver_ids,

        ];
        saveRideHistory($history_data);

        return json_message_response(__('message.bid_applied', ['id' => $request->ride_request_id, 'driver_name' => $auth_user->username]));
    }


    public function getBiddingDrivers(Request $request)
    {
        // Find the ride request
        $ride_request_id = $request->ride_request_id;
        $ride_request = RideRequest::find($ride_request_id);
        if (!$ride_request) {
            return response()->json(['error' => 'Ride request not found.'], 404);
        }

        $unit = $ride_request->distance_unit ?? 'km';
        $unit_value = convertUnitvalue($unit);
        $radius = Setting::where('type', 'DISTANCE')->where('key', 'DISTANCE_RADIUS')->pluck('value')->first() ?? 50;

        $latitude = $ride_request->start_latitude;
        $longitude = $ride_request->start_longitude;

        // Get nearby drivers who have bid on the ride
        $bidding_drivers = DB::table('ride_request_bids')
            ->join('users', 'ride_request_bids.driver_id', '=', 'users.id')
            ->select(
                'users.id as driver_id',
                'users.display_name as driver_name',
                'ride_request_bids.bid_amount',
                'ride_request_bids.notes',
                DB::raw("($unit_value * acos(cos(radians($latitude)) * cos(radians(users.latitude)) * cos(radians(users.longitude) - radians($longitude)) + sin(radians($latitude)) * sin(radians(users.latitude)))) AS distance")
            )
            ->where('ride_request_bids.is_bid_accept', 0)
            ->where('ride_request_bids.ride_request_id', $ride_request_id)
            ->where('users.status', 'active')
            ->where('users.is_online', 1)
            ->where('users.is_available', 1)
            ->having('distance', '<=', $radius)
            ->orderBy('distance', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $bidding_drivers,
            'start_address' => $ride_request->start_address,
            'end_address' => $ride_request->end_address,
            'multi_drop_location' => $ride_request->multi_drop_location,
        ]);
    }

    public function acceptBidRequest(Request $request)
    {
        $riderequest = RideRequest::find($request->id);

        if ($riderequest == null) {
            $message = __('message.not_found_entry', ['name' => __('message.riderequest')]);
            return json_message_response($message);
        }

        if ($riderequest->status == 'accepted') {
            $message = __('message.not_found_entry', ['name' => __('message.riderequest')]);
            return json_message_response($message, 400);
        }

        $driverIds = is_array(request('driver_id')) ? request('driver_id') : [request('driver_id')];

        if (request()->has('is_bid_accept') && request('is_bid_accept') == 1) {
            $riderequest->driver_id = $driverIds[0];
            $riderequest->status = 'bid_accepted';
            $riderequest->max_time_for_find_driver_for_ride_request = 0;
            $riderequest->otp = rand(1000, 9999);
            $riderequest->riderequest_in_driver_id = null;
            $riderequest->riderequest_in_datetime = null;
            $riderequest->save();

            $bid = RideRequestBid::where('ride_request_id', $riderequest->id)
                ->where('driver_id', $driverIds[0])
                ->first();

            if ($bid) {
                $bid->is_bid_accept = 1;
                $bid->save();
            }

            saveRideHistory([
                'history_type' => 'bid_accepted',
                'ride_request_id' => $riderequest->id,
                'ride_request' => $riderequest,
            ]);

            $riderequest->driver->update(['is_available' => 0]);

            $message = __('message.updated');
        } elseif (request()->has('is_bid_accept') && request('is_bid_accept') == 2) {
            $currentRejectedIds = json_decode($riderequest->rejected_bid_driver_ids, true) ?? [];
            if (!is_array($currentRejectedIds)) {
                $currentRejectedIds = [];
            }

            foreach ($driverIds as $driverId) {
                if (!in_array($driverId, $currentRejectedIds)) {
                    $currentRejectedIds[] = $driverId;
                }

                $bid = RideRequestBid::where('ride_request_id', $riderequest->id)
                    ->where('driver_id', $driverId)
                    ->first();

                if (!$bid) {
                    RideRequestBid::create([
                        'ride_request_id' => $riderequest->id,
                        'driver_id' => $driverId,
                        'bid_amount' => 0,
                        'is_bid_accept' => 2,
                    ]);
                } else {
                    $bid->is_bid_accept = 2;
                    $bid->save();
                }
            }

            $riderequest->update(['rejected_bid_driver_ids' => json_encode($currentRejectedIds)]);

            saveRideHistory([
                'history_type' => 'bid_rejected',
                'ride_request_id' => $riderequest->id,
                'ride_request' => $riderequest,
            ]);
        }

        $response = [
            'ride_request_id' => $riderequest->id,
            'message' => $message ?? __('message.save_form', ['form' => __('message.riderequest')]),
        ];

        if ($request->is('api/*')) {
            return json_custom_response($response);
        }

        return response()->json($response);
    }

    public function acceptRideRequest(Request $request)
    {
        if(request()->has('is_accept') && request('is_accept') == 1){
            $normal_ride_restriction_buffer = SettingData('ride', 'normal_ride_restriction_buffer') ?? 0;

            if($normal_ride_restriction_buffer > 0){
                $utcNow = Carbon::now('UTC');

                $hasUpcomingScheduledRide = RideRequest::where([
                        'driver_id' => request('driver_id'),
                        'is_schedule' => 1,
                        'status' => 'driver_accepted'
                    ])
                    ->whereBetween('scheduled_at', [
                        $utcNow,
                        $utcNow->copy()->addMinutes($normal_ride_restriction_buffer)
                    ])
                    ->exists();

                if ($hasUpcomingScheduledRide) {
                    $message = __('message.driver_normal_ride_restriction', [
                        'name' => __('message.riderequest'),
                        'minutes' => $normal_ride_restriction_buffer
                    ]);
                    return json_message_response($message);
                }
            }
        } 

        $riderequest = RideRequest::find($request->id);

        if($riderequest == null) {
            $message = __('message.not_found_entry', ['name' => __('message.riderequest')]);
            return json_message_response($message);
        }

        if( $riderequest->status != 'new_ride_requested' ) {
            $message = __('message.ride_already_accepted');
            return json_message_response($message,400);
        }
        if( request()->has('is_accept') && request('is_accept') == 1 ) {
            $riderequest->driver_id = request('driver_id');
            $riderequest->status = 'accepted';
            $riderequest->max_time_for_find_driver_for_ride_request = 0;
            $riderequest->otp = rand(1000, 9999);
            $riderequest->riderequest_in_driver_id = null;
            $riderequest->riderequest_in_datetime = null;
            $riderequest->save();
            $result = $riderequest;

            $history_data = [
                'history_type'      => 'accepted',
                'ride_request_id'   => $result->id,
                'ride_request'      => $result,
            ];
    
            saveRideHistory($history_data);
            $riderequest->driver->update(['is_available' => 0]);
            $message = __('message.updated');
        } else {
            // $riderequest->status = 'driver_declined';
            // $riderequest->save();

            // $result = $riderequest;
            // $history_data = [
            //     'history_type'      => 'driver_declined',
            //     'ride_request_id'   => $result->id,
            //     'ride_request'      => $result,
            // ];
    
            // saveRideHistory($history_data);
            $result = $this->acceptDeclinedRideRequest($riderequest, $request->all());

            // return response if ride request is decline by driver
            $message = __('message.ride.driver_declined',[ 'name' => __('message.driver') ] );
        }

        if(isset($result->driver_id) && $result->driver_id == null ) {
            $message = __('message.save_form',[ 'form' => __('message.riderequest') ] );
        }
        if($request->is('api/*')) {
            $response = [
                'ride_request_id' => $result->id ?? $request->id,
                'message' => $message
            ];
            return json_custom_response($response);
		}
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        if (!auth()->user()->can('riderequest show')) {
            abort(403, __('message.action_is_unauthorized'));
        }
        $pageTitle = __('message.add_form_title',[ 'form' => __('message.riderequest')]);
        $data = RideRequest::findOrFail($id);

        if( $data != null ) {
            $auth_user = auth()->user();
            if (count($auth_user->unreadNotifications) > 0) {
                $auth_user->unreadNotifications->where('data.type','!=', 'complaintcomment')->where('data.id', $id)->markAsRead();
            }
        }

        if ($data->duration <= $data->distance) {
            // Short duration ride
            $time_fare_value = $data->time_fare_short_ride;
            $time_fare_type = 'Short duration ride';
        } elseif ($data->duration > $data->distance && $data->duration <= 2 * $data->distance) {
            // Moderate duration ride
            $time_fare_value = $data->time_fare_moderate_ride;
            $time_fare_type = 'Moderate duration ride';
        } elseif ($data->duration > 2 * $data->distance) {
            // Long duration / heavy traffic
            $time_fare_value = $data->time_fare_long_ride;
            $time_fare_type = 'Long duration / heavy traffic';
        } else {
            $time_fare_value = 0;
            $time_fare_type = 'Other';
        }

        // $fixed_amount = 0;
        // $surge_price_setting_value = SettingData('ride', 'surge_price') ?? null;
        // if ($surge_price_setting_value == 1) {
        //     $service_id = $data->service_id;
        //     $region_id = Service::where('id', $service_id)->pluck('region_id')->first();
        //     $surge_price = getSurgePrice($data->datetime, $region_id, $data->start_latitude, $data->start_longitude, $data->end_latitude, $data->end_longitude);
        //     if (isset($surge_price) && !empty($surge_price)) {
        //         if ($surge_price->type == 'fixed') {
        //             $fixed_amount = $surge_price->value;
        //         } elseif ($surge_price->type == 'percentage') {
        //             $fixed_amount =($data->subtotal * $surge_price->value) / 100;
        //         }
        //     }
        //     return view('riderequest.show', compact('data','surge_price','fixed_amount','time_fare_value','time_fare_type'));
        // }
        $fixed_amount = $data->surge_amount ? (float) number_format( (float) $data->surge_amount, 2,'.','') : 0;
        return view('riderequest.show', compact('data','fixed_amount','time_fare_value','time_fare_type'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $pageTitle = __('message.update_form_title',[ 'form' => __('message.riderequest')]);
        $data = RideRequest::findOrFail($id);
        
        return view('riderequest.form', compact('data', 'pageTitle', 'id'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(RideRequestRequest $request, $id)
    {
        $riderequest = RideRequest::findOrFail($id);
        $ride_status = $riderequest->status;

        if($ride_status == 'canceled' && $request->status == 'arriving'){
            $message = __('message.ride_already_canceled' );
            return json_message_response($message);
        }

        if( $request->has('otp') ) {
            if($riderequest->otp != $request->otp) {
                return json_message_response(__('message.otp_invalid'), 400);
            }
        }
        // RideRequest data...
        $riderequest->fill($request->all())->update();
        $message = __('message.update_form',[ 'form' => __('message.riderequest') ] );
        if($riderequest->status == 'new_ride_requested') {
            if($riderequest->riderequest_in_driver_id == null) {
                $this->acceptDeclinedRideRequest($riderequest, $request->all());
            }
            if($request->is('api/*')) {
                return json_message_response($message);
            }
        }
        $payment = Payment::where('ride_request_id',$id)->first();

        if( $request->has('is_change_payment_type') && request('is_change_payment_type') == 1 )
        {
            $payment->update(['payment_type' => request('payment_type')]);

            $message = __('message.change_payment_type');
            $notify_data = new \stdClass();
            $notify_data->success = true;
            $notify_data->success_type = 'change_payment_type';
            $notify_data->success_message = $message;
            $notify_data->result = new RideRequestResource($riderequest);

            try {
                $document_name = 'ride_' . $riderequest->id;
                $firebaseData = app('firebase.firestore')->database()->collection('rides')->document($document_name);

                $rideData = [
                    'driver_ids' => [$riderequest->driver_id],
                    'on_rider_stream_api_call' => 1,
                    'on_stream_api_call' => 1,
                    'payment_status' => $riderequest->payment->payment_status,
                    'payment_type' => $riderequest->payment->payment_type,
                    'ride_id' => $riderequest->id,
                    'rider_id' => $riderequest->rider_id,
                    'status' => $riderequest->status,
                    'tips' => $riderequest->tips ? 1 : 0,
                ];
        
                if ($riderequest->status == 'canceled') {
                    sleep(3);
                    $firebaseData->delete();
                } else {
                    $firebaseData->set($rideData, ['merge' => true]);
                }
        
            } catch (\Exception $e) {
                \Log::error('Error updating Firestore document for Ride:-405 ' . $e->getMessage());
            }
            // dispatch(new NotifyViaMqtt('ride_request_status_'.$riderequest->driver_id, json_encode($notify_data)));

            return json_message_response($message);
        }
        
        $history_data = [
            'history_type'      => request('status'),
            'ride_request_id'   => $id,
            'ride_request'      => $riderequest,
        ];

        saveRideHistory($history_data);

        if($request->status == 'canceled'){
            // Check who cancelled the ride
                $cancelled_by = $request->cancel_by;

                if ($cancelled_by === 'rider') {
                    // Rider canceled → check ride status
                    if ($ride_status === 'arrived' && $riderequest->cancelation_charges > 0) {
                        // Rider canceled AFTER driver arrived → apply cancellation charges
                        $this->saveCancellationPayment($riderequest, $ride_status);
                    } else {
                        // Rider canceled BEFORE driver arrived → just release hold (no charges)
                        if (hasStripeKeys() && $riderequest->payment_type === 'card' && !empty($riderequest->held_payment_intent_id)) {
                            cancelStripePayment($riderequest->held_payment_intent_id);
                        }
                    }
                } else {
                    // Driver canceled → always release hold (no rider charges)
                    if (hasStripeKeys() && $riderequest->payment_type === 'card' && !empty($riderequest->held_payment_intent_id)) {
                        cancelStripePayment($riderequest->held_payment_intent_id);
                    }
                }

            try {
                $firebaseData = app('firebase.firestore')
                    ->database()
                    ->collection('rides')
                    ->document('ride_'.$riderequest->id);
        
                $firebaseData->delete();
            } catch (\Exception $e) {
                Log::error("Firestore delete failed for ride {$riderequest->id}: ".$e->getMessage());
            }
        }

        if($request->is('api/*')) {
            return json_message_response($message);
		}

        if(auth()->check()){
            return redirect()->route('riderequest.index')->withSuccess(__('message.update_form',['form' => __('message.riderequest')]));
        }
        return redirect()->back()->withSuccess(__('message.update_form',['form' => __('message.riderequest') ] ));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if(env('APP_DEMO')){
            $message = __('message.demo_permission_denied');
            if(request()->ajax()) {
                return response()->json(['status' => true, 'message' => $message ]);
            }
            return redirect()->route('riderequest.index')->withErrors($message);
        }
        $riderequest = RideRequest::find($id);
        $status = 'errors';
        $message = __('message.not_found_entry', ['name' => __('message.riderequest')]);

        if($riderequest != '') {
            $search = "id".'":'.$id;
            Notification::where('data','like',"%{$search}%")->delete();

            $document_name = 'ride_' . $riderequest->id;
            $firebaseData = app('firebase.firestore')->database()->collection('rides')->document($document_name);
            $firebaseData->delete();
            $riderequest->delete();
            
            $status = 'success';
            $message = __('message.delete_form', ['form' => __('message.riderequest')]);
        }

        if(request()->is('api/*')){
            return json_message_response( $message );
        }

        if(request()->ajax()) {
            return response()->json(['status' => true, 'message' => $message ]);
        }

        return redirect()->back()->with($status,$message);
    }

    public function rideInvoicePdf($id, $user_type = null)
    {
        $user_type = $user_type ? $user_type : 'admin';
        $ride_detail = RideRequest::find($id);
        $today = now()->format('d/m/Y');
        $app_setting = AppSetting::first();

        // $surge_price_setting_value = SettingData('ride', 'surge_price') ?? null;
        // if ($surge_price_setting_value == 1) {
        //     $service_id = $ride_detail->service_id;
        //     $region_id = Service::where('id', $service_id)->pluck('region_id')->first();

        //     $surge_price = getSurgePrice($ride_detail->datetime, $region_id, $ride_detail->start_latitude, $ride_detail->start_longitude, $ride_detail->end_latitude, $ride_detail->end_longitude);
        // }
        
        
        // if (isset($surge_price) && !empty($surge_price)) {
        //     if ($surge_price->type == 'fixed') {
        //         $fixed_charge = $surge_price->value;
        //     } elseif ($surge_price->type == 'percentage') {
        //         $fixed_charge =($ride_detail->subtotal * $surge_price->value) / 100;
        //     }
        // } else {
        //     $fixed_charge = 0;
        // }
        $fixed_charge = $ride_detail->surge_amount ? (float) number_format( (float) $ride_detail->surge_amount, 2,'.','') : 0;
        // return view('riderequest.invoice',compact('ride_detail','today','app_setting','fixed_charge'),[]);

        $pdf = Pdf::loadView('riderequest.invoice', compact('ride_detail','today','app_setting','fixed_charge','user_type'),[]);
        if(request()->is('api/*')){
            return $pdf->stream('ride_' . $ride_detail->id . '.pdf');
        }
        return $pdf->stream('invoice_' . $ride_detail->id . '.pdf');       
    }

    public function updateDropLocationTime($rideId, $dropIndex)
    {
        $ride = RideRequest::findOrFail($rideId);
        $multiLocation = json_decode($ride->multi_drop_location, true);

        if (isset($multiLocation[$dropIndex])) {
            $multiLocation[$dropIndex]['dropped_at'] = now();
            $ride->multi_drop_location = json_encode($multiLocation);
            $ride->save();

            return response()->json([
                'success' => true,
                'message' => 'Drop time recorded successfully',
                'multi_drop_location' => $multiLocation
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Invalid drop index'
        ], 400);
    }

    /**
     * Scheduled rides for the authenticated rider
     */
    public function saveScheduleRide(Request $request){
        $data = $request->all();
        $service = Service::with('region')->where('id',$request->service_id)->first();

        // Check if this is a scheduled ride
        if ($request->has('is_schedule') && $request->is_schedule == 1) {
            // Validate schedule datetime
            if(!$request->has('datetime')){
                return json_message_response(__('message.ride.schedule_datetime_required'), 400);
            }

            if(!$request->has('timezone')){
                return json_message_response(__('message.ride.timezone_required'), 400);
            }

            $riderTimezone = $request->timezone;
            $riderScheduleDateTime = Carbon::createFromFormat('Y-m-d H:i:s', $request->datetime, $riderTimezone);
            
            // Ensure schedule time is in the future
            if($riderScheduleDateTime->isPast()){
                return json_message_response(__('message.ride.schedule_time_must_be_future'), 400);
            }

            // Check if schedule time is at least 60 mins in advance
            $minScheduleTime = now()->addMinutes(60);
            if($riderScheduleDateTime->lt($minScheduleTime)){
                return json_message_response(__('message.ride.schedule_minimum_time_required'), 400);
            }

            // Convert to UTC to compare & store
            $scheduledAtUTC = $riderScheduleDateTime->clone()->setTimezone('UTC');

            // Add buffer (x minutes before & after)
            $scheduled_ride_restriction_buffer = SettingData('ride', 'scheduled_ride_restriction_buffer') ?? 0;
            $startTime = $scheduledAtUTC->clone()->subMinutes($scheduled_ride_restriction_buffer);
            $endTime   = $scheduledAtUTC->clone()->addMinutes($scheduled_ride_restriction_buffer);

            // Check if rider already has a ride in this time window
            $existingRide = RideRequest::where('rider_id', $request->rider_id)
                ->where('is_schedule', 1)
                ->whereIn('status', ['scheduled', 'driver_accepted'])
                ->whereBetween('scheduled_at', [$startTime->toDateTimeString(), $endTime->toDateTimeString()])
                ->first();

            if($existingRide){
                return json_message_response(__('message.ride.already_scheduled_same_time'), 400);
            }
            
            $data['datetime'] = $request->datetime; // store schedule dateTime in rider timezone
            $data['scheduled_at'] = $scheduledAtUTC; // store schedule dateTime in UTC

            $data['is_schedule'] = 1;
            $data['status'] = 'scheduled';
        }
        
        $coupon_code = $request->coupon_code;

        if( $coupon_code != null ) {
            $coupon = Coupon::where('code', $coupon_code)->first();
            $status = isset($coupon_code) ? 400 : 200;
        
            if($coupon != null) {
                $status = Coupon::isValidCoupon($coupon);
            }
            if( $status != 200 ) {
                $response = couponVerifyResponse($status);
                return json_custom_response($response,$status);
            } else {
                $data['coupon_code'] = $coupon->id;
                $data['coupon_data'] = $coupon;
            }
        }

        $data['distance_unit'] = $service->region->distance_unit ?? 'km';
        if (isset($data['multi_location'])) {
            $data['multi_drop_location'] = json_encode($data['multi_location']);
        }
        $data['ride_has_bid'] = $request->ride_type == 'with_bidding' ? 1 : 0;
        $result = RideRequest::create($data);

        $message = __('message.save_form', ['form' => __('message.riderequest')]);        
        
        $history_data = [
            'ride_request_id' => $result->id,
            'history_type'    => $result->status,
            'ride_request'    => $result,
            'driver_ids'      => $result,
        ];
        saveRideHistory($history_data);
        
        if($request->is('api/*')) {
            $response = [
                'riderequest_id' => $result->id,
                'message' => $message
            ];
            return json_custom_response($response);
        }

        return redirect()->route('riderequest.index')->withSuccess($message);
    }

    /**
     * Cancel a scheduled ride
     */
    public function cancelScheduledRide(Request $request, $id)
    {
        $ride = RideRequest::where('id', $id)->whereIn('status', ['scheduled','driver_accepted','accepted'])->where('is_schedule', 1)->first();

        if (!$ride) {
            return json_message_response(__('message.ride.scheduled_ride_not_found'), 404);
        }        

        
        $user = auth()->user();  
        // Rider has permission to cancel his schedule rides
        if(!$user->hasRole('admin') && $user->id !== $ride->rider_id) {
            return json_message_response(__('message.ride.unauthorized_action'), 403);
        }

        // Don't allow cancellation if less than 30 mins before scheduled time
        $scheduleTime = \Carbon\Carbon::parse($ride->datetime);
        if ($scheduleTime->diffInMinutes(now()) < 30) {
            return json_message_response(__('message.ride.too_late_to_cancel_scheduled_ride'), 400);
        }

        $ride->status = 'canceled';
        $ride->cancel_by = $user->user_type;
        $ride->reason = $request->cancel_reason;
        $ride->save();

        $history_data = [
            'history_type' => 'canceled',
            'ride_request_id' => $ride->id,
            'ride_request' => $ride,
        ];
        saveRideHistory($history_data);

        if ($request->is('api/*')) {
            return json_message_response(__('message.ride.scheduled_ride_cancelled_successfully'));
        }

        return redirect()->back()->withSuccess(__('message.ride.scheduled_ride_cancelled_successfully'));
    }

    /**
     * Accept a scheduled ride
     */
    public function acceptScheduleRide(Request $request, $id){
        $riderequest = RideRequest::find($id);

        if($riderequest == null) {
            $message = __('message.not_found_entry', ['name' => __('message.riderequest')]);
            return json_message_response($message);
        }

        if($riderequest->status != 'scheduled' ) {
            $message = __('message.ride.riderequest_status_is_not_scheduled');
            return json_message_response($message,400);
        }

        if($riderequest->driver_id != null ) {
            $message = __('message.ride.driver_assigned');
            return json_message_response($message,400);
        }

        $user = auth()->user();
        // Driver has permission to accept the schedule rides
        if(!$user->hasRole('driver')){
            return json_message_response(__('message.ride.unauthorized_action'), 403);
        }
    // Step 1: Documents not verified
        if ($user->is_verified_driver != 1 ) {
            return json_message_response(__('message.ride.driver_not_verified'));
        }
 
        if( $user->status != 'active') {
            return json_message_response(__('message.ride.driver_not_active'));
        }
        // if(!request()->has('is_accept') && request('is_accept') == 0 ) {
        //     $message = __('message.not_found_entry', ['name' => __('message.riderequest')]);
        //     return json_message_response($message,400);
        // }

        $scheduledTime = Carbon::parse($riderequest->scheduled_at);
        $now = Carbon::now();
        
        // Reject if ride is within 30 minutes from now or in the past
        if ($scheduledTime->lte($now->addMinutes(30))) {
            return json_message_response(__('message.ride.must_be_after_30_minutes'), 400);
        }

        $hasActiveSchedule = RideRequest::where([
            'driver_id'   => $user->id,
            'is_schedule' => 1,
        ])
        ->whereNotIn('status', ['completed', 'canceled'])
        ->exists();

        if ($hasActiveSchedule) {
            $message = __('message.ride.schedule_already_active');
            return json_message_response($message,400);
        }

        if(request()->has('is_accept') && request('is_accept') == 1 ){
            $riderequest->driver_id = request('driver_id');
            $riderequest->status = 'driver_accepted';
            $riderequest->max_time_for_find_driver_for_ride_request = 0;
            $riderequest->otp = rand(1000, 9999);
            $riderequest->riderequest_in_driver_id = null;
            $riderequest->riderequest_in_datetime = null;
            $riderequest->save();
            $result = $riderequest;
        
            $history_data = [
                'history_type'      => 'driver_accepted',
                'ride_request_id'   => $result->id,
                'ride_request'      => $result,
            ];
        
            saveRideHistory($history_data);
            //$riderequest->driver->update(['is_available' => 0]);

            $message = __('message.updated');
        }else{
            $cancelled_driver_ids = $riderequest->cancelled_driver_ids ?: [];
        
            if (request()->has('is_accept') && request('is_accept') == 0) {
                array_push($cancelled_driver_ids, request('driver_id'));
            }
            $riderequest->cancelled_driver_ids = $cancelled_driver_ids;
            $result = $riderequest->save();

            $message = __('message.ride.driver_declined',[ 'name' => __('message.driver') ] );
        }        
        
        if(isset($result->driver_id) && $result->driver_id == null ) {
            $message = __('message.save_form',[ 'form' => __('message.riderequest') ] );
        }
        if($request->is('api/*')) {
            $response = [
                'ride_request_id' => $result->id ?? $id,
                'message' => $message
            ];
            return json_custom_response($response);
        }
    }
    /**
     * Assiged driver to schedule rides
     */
    public function assignDriverToScheduleRide(Request $request, $id){
        $riderequest = RideRequest::find($id);

        if($riderequest == null) {
            $message = __('message.not_found_entry', ['name' => __('message.riderequest')]);
            return json_message_response($message);
        }

        if($riderequest->status != 'scheduled' ) {
            $message = __('message.not_found_entry', ['name' => __('message.riderequest')]);
            return json_message_response($message,400);
        }

        $riderequest->update([
            'riderequest_in_driver_id' => $request->driver_id,
            'riderequest_in_datetime' => Carbon::now()->format('Y-m-d H:i:s')
        ]);

        // Send notification to the assigned driver
        $notification_data = [
            'id' => $riderequest->id,
            'type' => 'scheduled',
            'data' => [
                'rider_id' => $riderequest->rider_id,
                'rider_name' => optional($riderequest->rider)->display_name ?? '',
            ],
            'message' => __('message.scheduled'),
            'subject' => __('message.ride.scheduled'),
        ];

        $driver = User::find($request->driver_id);
        $driver->notify(new CommonNotification($notification_data['type'], $notification_data));

        $message = __('message.ride.driver_assigned_to_schedule_riderequest'); 
        return redirect()->route('riderequest.index')->withSuccess($message);
    }


    private function saveCancellationPayment($ride, $ride_status){
        try {
            $held_payment_intent_id = $ride->held_payment_intent_id;
            $held_payment_amount    = $ride->held_payment_amount ?? 0;
            $cancelation_charges    = $ride->cancelation_charges ?? 0;
            $user                   = User::find($ride->rider_id);
            $customer_id            = $user->stripe_customer_id;
    
            $currency_code = $currency ?? SettingData('CURRENCY', 'CURRENCY_CODE') ?? 'USD';
            $currency_data = currencyArray($currency_code);
            $currency      = strtolower($currency_data['code']);
    
            if ($ride->payment_type === 'card' && !empty($held_payment_intent_id) && hasStripeKeys()) {
                try {
                    // If charges > held amount → create new intent
                    if ($cancelation_charges > $held_payment_amount) {
                        $cancelRes = cancelStripePayment($held_payment_intent_id);
                        if (!$cancelRes['success']) {
                            Log::warning("Cancel old payment intent failed", [
                                'ride_id' => $ride->id,
                                'error'   => $cancelRes['error'] ?? null,
                            ]);
                        }
    
                        $newIntent = createStripePaymentIntent(
                            $customer_id,
                            $cancelation_charges * 100,
                            $currency
                        );
    
                        $held_payment_intent_id = $newIntent['id'] ?? null;
                        if (!$held_payment_intent_id) {
                            throw new \Exception("Failed to create new Stripe PaymentIntent");
                        }
                    }
    
                    // Try capturing Stripe payment
                    $captureRes = captureStripePaymentIntent($held_payment_intent_id, $cancelation_charges * 100);
    
                    if ($captureRes['success']) {
                        // Stripe capture succeeded
                        $this->saveCancellationTransaction($ride, $currency);
                    } else {
                        throw new \Exception("Stripe capture failed: " . json_encode($captureRes['error'] ?? []));
                    }
                } catch (\Throwable $stripeEx) {
                    // Stripe capture failed → fallback to wallet
                    Log::error('Stripe cancellation capture failed', [
                        'ride_id' => $ride->id,
                        'error'   => $stripeEx->getMessage(),
                    ]);
    
                    $this->saveCancellationTransaction($ride, $currency, true); // true = forced wallet
                }
            } else {
                // No Stripe keys or non-card flow → directly debit wallet
                $this->saveCancellationTransaction($ride, $currency, true);
            }
        } catch (\Throwable $th) {
            Log::error('Cancellation payment processing failed', [
                'ride_id' => $ride->id,
                'error'   => $th->getMessage(),
            ]);
    
            // As last fallback → debit wallet
            $this->saveCancellationTransaction($ride, $currency, true);
        }
    }

    private function saveCancellationTransaction($ride, $default_currency, $forceWallet = false){
        DB::transaction(function () use ($ride, $default_currency, $forceWallet) {

            // Update ride amounts
            $ride->total_amount += $ride->cancelation_charges;
            //$ride->subtotal     += $ride->cancelation_charges;
            $ride->save();
    
            $currency = $default_currency ?? 'USD';

            // Always received by admin for cancellation fees
            $received_by = 'admin';
    
            // Store payment record
            $payment = Payment::create([
                'rider_id'          => $ride->rider_id,
                'ride_request_id'   => $ride->id,
                'datetime'          => now(),
                'total_amount'      => $ride->total_amount,
                'credit_used'       => 0,
                'admin_commission'  => 0,
                'received_by'       => $received_by,
                'driver_fee'        => 0,
                'driver_tips'       => 0,
                'driver_commission' => 0,
                'fleet_commission'  => 0,
                'company_fee_charge'=> 0,
                'expenses_charge'   => 0,
                'payment_type'      => $forceWallet ? 'wallet' : $ride->payment_type,
                'payment_status'    => 'paid',
            ]);
    
            // Deduct from rider wallet if wallet payment or forced wallet
            if ($ride->payment_type == 'wallet' || $forceWallet) {
                $rider_wallet = Wallet::firstOrCreate(['user_id' => $ride->rider_id]);
                $rider_wallet->total_amount -= $ride->cancelation_charges;
                $rider_wallet->save();
    
                WalletHistory::create([
                    'user_id'           => $ride->rider_id,
                    'type'              => 'debit',
                    'currency'          => $currency,
                    'transaction_type'  => 'cancellation_fee',
                    'amount'            => $ride->cancelation_charges,
                    'balance'           => $rider_wallet->total_amount,
                    'datetime'          => now(),
                    'ride_request_id'   => $ride->id,
                ]);
            }
    
            // Credit to driver wallet
            $driver_id = $ride->driver_id;
            $driver_wallet = Wallet::firstOrCreate(['user_id' => $driver_id]);
            $driver_wallet->total_amount += $ride->cancelation_charges;
            $driver_wallet->save();
    
            WalletHistory::create([
                'user_id'           => $driver_id,
                'type'              => 'credit',
                'transaction_type'  => 'cancellation_fee',
                'currency'          => $currency,
                'amount'            => $ride->cancelation_charges,
                'balance'           => $driver_wallet->total_amount,
                'ride_request_id'   => $ride->id,
                'datetime'          => now(),
                'data'              => [
                    'payment_id' => $payment->id
                ]
            ]);
        });
    }

    /**
     * Deline a accepted scheduled ride
     */
    public function declineAcceptedScheduleRide(Request $request, $id){
        $riderequest = RideRequest::find($id);

        if(!$riderequest) {
            $message = __('message.not_found_entry', ['name' => __('message.riderequest')]);
            return json_message_response($message, 404);
        }

        if($riderequest->status != 'driver_accepted') {
            $message = __('message.ride.riderequest_status_is_not_driver_accepted');
            return json_message_response($message, 400);
        }

        $user = auth()->user();

        if(!$user->hasRole('driver')){
            return json_message_response(__('message.ride.unauthorized_action'), 403);
        }

        // Decline logic
        $riderequest->driver_id = null;
        $riderequest->status = 'scheduled';
        $riderequest->otp = null;
        $riderequest->max_time_for_find_driver_for_ride_request = 0;
        $riderequest->riderequest_in_driver_id = null;
        $riderequest->riderequest_in_datetime = null;

        // Update cancelled drivers list
        $cancelled_driver_ids = $riderequest->cancelled_driver_ids ?: [];
        $cancelled_driver_ids[] = $user->id; 
        $riderequest->cancelled_driver_ids = array_values(array_unique($cancelled_driver_ids));

        $riderequest->save();

        // Save ride history (with driver info)
        $history_data = [
            'rider_id'               => $riderequest->rider_id,
            'rider_name'             => $riderequest->rider->display_name ?? null,
            'last_declined_driver_id'=> $user->id,
            'last_declined_driver_name'=> $user->display_name ?? null,
        ];

        RideRequestHistory::create([
            'history_type'      => 'scheduled',
            'history_message'   => 'Rescheduled',
            'ride_request_id'   => $riderequest->id,
            'datetime'          => date('Y-m-d H:i:s'),
            'ride_request'      => $riderequest,
            'history_data'      => json_encode($history_data),
        ]);

        $message = __('message.ride.driver_declined',[ 'name' => __('message.driver') ] );

        if($request->is('api/*')) {
            return json_custom_response([
                'ride_request_id' => $riderequest->id,
                'message' => $message
            ]);
        }
    }
}
