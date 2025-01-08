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
        $riderequest = RideRequest::find($request->id);

        if($riderequest == null) {
            $message = __('message.not_found_entry', ['name' => __('message.riderequest')]);
            return json_message_response($message);
        }

        if( $riderequest->status == 'accepted' ) {
            $message = __('message.not_found_entry', ['name' => __('message.riderequest')]);
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
        }

        $message = __('message.updated');
        if( $result->driver_id == null ) {
            $message = __('message.save_form',[ 'form' => __('message.riderequest') ] );
        }
        if($request->is('api/*')) {
            $response = [
                'ride_request_id' => $result->id,
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
        $fixed_amount = 0;
        $surge_price_setting_value = SettingData('ride', 'surge_price') ?? null;
        if ($surge_price_setting_value == 1) {
            $surge_price = getSurgePrice($data->datetime);
            if (isset($surge_price) && !empty($surge_price)) {
                if ($surge_price->type == 'fixed') {
                    $fixed_amount = $surge_price->value;
                } elseif ($surge_price->type == 'percentage') {
                    $fixed_amount =($data->subtotal * $surge_price->value) / 100;
                }
            }
            return view('riderequest.show', compact('data','surge_price','fixed_amount'));
        }
        return view('riderequest.show', compact('data','fixed_amount'));
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

    public function rideInvoicePdf($id)
    {
        $ride_detail = RideRequest::find($id);
        $today = now()->format('d/m/Y');
        $app_setting = AppSetting::first();
        $surge_price = getSurgePrice($ride_detail->datetime);
        
        if (isset($surge_price) && !empty($surge_price)) {
            if ($surge_price->type == 'fixed') {
                $fixed_charge = $surge_price->value;
            } elseif ($surge_price->type == 'percentage') {
                $fixed_charge =($ride_detail->subtotal * $surge_price->value) / 100;
            }
        } else {
            $fixed_charge = 0;
        }
        // return view('riderequest.invoice',compact('ride_detail','today','app_setting','fixed_charge'),[]);

        $pdf = Pdf::loadView('riderequest.invoice', compact('ride_detail','today','app_setting','fixed_charge'),[]);
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
}
