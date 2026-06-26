<?php
use App\Models\User;
use App\Models\AppSetting;
use App\Models\Setting;
use App\Models\RideRequest;
use App\Models\RideRequestHistory;
use App\Models\Coupon;
use App\Notifications\RideNotification;
use App\Notifications\CommonNotification;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use App\Http\Resources\RideRequestResource;
use App\Models\Document;
use App\Models\LanguageVersionDetail;
use App\Models\RideRequestBid;
use App\Models\SurgePrice;
use App\Models\Referral;
use App\Models\Point;
use App\Models\PointHistory;
use App\Models\Wallet;
use App\Models\WalletHistory;
use App\Models\Region;
use App\Models\Role;
use App\Models\PaymentGateway;
use App\Models\RideTip;

function DummyData($key){
    $dummy_title = 'XXXXXXXXXXXX';
    $dummy_description = 'xxxxxxxxxxxxxxxx xxxxxxxxx xxxxxxxxxxxxxxxxxxxxx xxxxxxxxxxxxxxxxxxxxxx xxxxxxxxxxxxxxxxxxxxxxxx xxxxxxxxxxxxxxxxxxxxxxxx xxxxxxxxx xxxxxxxxxxx xxxxxxxxxxxxxxxxx';

    switch ($key) {
        case 'dummy_title':
            return $dummy_title;
        case 'dummy_description':
            return $dummy_description;
        default:
            return '';
    }
}

function getSettingFirstData($type = null, $key = null)
{
    return Setting::where('type', $type)->where('key', $key)->first();
}

function getSingleMediaSettingImage($model, $collection_name, $check_collection_type = null)
{
    $image = null;
    if ($model !== null) {
        $image = $model->getFirstMedia($collection_name);
    }

    if ($image !== null && getFileExistsCheck($image))
    {
        return $image->getFullUrl();
    }else{
        switch ($collection_name) {
            case 'logo_image':
                $image = asset('frontend-website/img/40x40_black_white_logo.png');
                break;
            case 'background_image':
                $image = asset('frontend-website/img/1920x995.png');
                break;
            case 'frontend_data_image':
                if ($check_collection_type == 'testimonial_image') {
                    $image = asset('frontend-website/img/1920x800.png');
                }
                if ($check_collection_type == 'whychoose_image') {
                    $image = asset('frontend-website/img/200x200.png');
                }
                break;
            case 'download_app_logo':
                $image = asset('frontend-website/img/600x591.png');
                break;
            case 'image':
                if ($check_collection_type == 'contactus_info') {
                    $image = asset('frontend-website/img/1920x437.png');
                }
                if ($check_collection_type == 'download_app') {
                    $image = asset('frontend-website/img/600x591.png');
                }
                if ($check_collection_type == 'our_mission') {
                    $image = asset('frontend-website/img/612x597.png');
                }
                if ($check_collection_type == 'why_choose') {
                    $image = asset('frontend-website/img/1920x800.png');
                }
                break;
        }
        return $image;
    }

}

function authSession($force = false) {
    $session = new User;
    if( $force ) {
        $user = auth()->user()->getRoleNames();
        Session::put('auth_user',$user);
        $session = Session::get('auth_user');
        return $session;
    }
    if ( Session::has('auth_user') ) {
        $session = Session::get('auth_user');
    } else {
        $user = auth()->user();
        Session::put('auth_user',$user);
        $session = Session::get('auth_user');
    }
    return $session;
}

function appSettingData($type = 'get')
{
    if(Session::get('setting_data') == ''){
        $type='set';
    }
    switch ($type){
        case 'set' :
            $settings = AppSetting::first();
            Session::put('setting_data',$settings);
            break;
        default :
            break;
    }
    return Session::get('setting_data');
}

function json_message_response( $message, $status_code = 200)
{	
	return response()->json( [ 'message' => $message ], $status_code );
}

function json_custom_response( $response, $status_code = 200 )
{
    return response()->json($response,$status_code);
}

function json_pagination_response($items)
{
    return [
        'total_items' => $items->total(),
        'per_page' => $items->perPage(),
        'currentPage' => $items->currentPage(),
        'totalPages' => $items->lastPage()
    ];
}

function imageExtention($media)
{
    $extention = null;
    if($media != null){
        $path_info = pathinfo($media);
        $extention = $path_info['extension'];
    }
    return $extention;
}

function saveRideHistory($data)
{
    $user_type = auth()->user()->user_type;
    $data['datetime'] = date('Y-m-d H:i:s');
    // $mqtt_event = 'test_connection';
    $history_data = [];
    $sendTo = [];
    
    $ride_request_id = $data['ride_request']->id;
    $ride_request = RideRequest::find($ride_request_id);
    switch ($data['history_type']) {
        case 'scheduled':
            $data['history_message'] = __('message.ride.scheduled');
            $history_data = [
                'rider_id' => $ride_request->rider_id,
                'rider_name' => optional($ride_request->rider)->display_name ?? '',
            ];
            $sendTo = ['admin', 'rider'];
            break;
        case 'driver_accepted':
            $data['history_message'] = __('message.ride.driver_accepted');
            $history_data = [
                'driver_id' => $ride_request->driver_id,
                'driver_name' => optional($ride_request->driver)->display_name ?? '',
            ];
            $sendTo = ['admin', 'rider'];
            break;
        case 'new_ride_requested':
            $data['history_message'] = __('message.ride.new_ride_requested');
            $history_data = [
                'rider_id' => $ride_request->rider_id,
                'rider_name' => optional($ride_request->rider)->display_name ?? '',
            ];
            $sendTo = [];
            break;
        case 'booked':
            $data['history_message'] = __('message.ride.booked');
            $history_data = [
                'rider_id' => $ride_request->rider_id,
                'rider_name' => optional($ride_request->rider)->display_name ?? '',
            ];
            $sendTo = removeValueFromArray(['admin', 'rider'], $user_type);
            break;
        
        case 'no_drivers_available':
            # code...
            break;

        // case 'finding_drivers':
        //     $data['history_message'] = __('message.ride.finding_drivers');
        //     $history_data = [
        //         'rider_id' => $ride_request->rider_id,
        //         'rider_name' => optional($ride_request->rider)->display_name ?? '',
        //     ];
        //     $sendTo = [];
        //     break;
        // case 'driver_found':
        //     $data['history_message'] = __('message.ride.driver_found',['name' => optional($ride_request->riderequest_in_driver)->display_name]);
        //     $history_data = [
        //         'driver_id' => $ride_request->driver_id,
        //         'driver_name' => optional($ride_request->driver)->display_name ?? '',
        //     ];
        //     $sendTo = ['admin', 'driver'];
        //     break;
        // case 'driver_not_found':
        //     $data['history_message'] = __('message.ride.driver_not_found');
        //     $history_data = [
        //         'driver_id' => $ride_request->driver_id,
        //         'driver_name' => optional($ride_request->driver)->display_name ?? '',
        //     ];
        //     $sendTo = ['admin', 'rider'];
        //     break;
        // case 'driver_declined':
        //     $data['history_message'] = __('message.ride.driver_declined',['name' => optional($ride_request->riderequest_in_driver)->display_name]);
        //     $history_data = [
        //         'driver_id' => $ride_request->riderequest_in_driver,
        //         'driver_name' => optional($ride_request->driver)->display_name ?? '',
        //     ];
        //     // $mqtt_event = 'ride_request_status';
        //     $sendTo = removeValueFromArray(['admin', 'rider'], $user_type);
        //     break;
        // case 'awaiting_driver_response':
        //     $data['history_message'] = __('message.ride.awaiting_driver_response');
        //     $history_data = [
        //         'driver_id' => $ride_request->driver_id,
        //         'driver_name' => optional($ride_request->driver)->display_name ?? '',
        //     ];
        //     $sendTo = ['driver'];
        //     break;
        case 'accepted':
            $data['history_message'] = __('message.ride.accepted');
            $history_data = [
                'driver_id' => $ride_request->driver_id,
                'driver_name' => optional($ride_request->driver)->display_name ?? '',
            ];
            // $mqtt_event = 'ride_request_status';
            $sendTo = removeValueFromArray(['admin', 'rider'], $user_type);
            break;

        case 'bid_placed':
            foreach ($ride_request->bids as $bid) {
                $data['history_message'] = __('message.ride.bid_placed', ['name' => optional($bid->driver)->display_name]);
                $history_data = [
                    'driver_id' => $bid->driver_id,
                    'rider_id' => $ride_request->rider_id,
                    'driver_name' => optional($bid->driver)->display_name ?? '',
                ];
                $sendTo = removeValueFromArray(['admin', 'rider'], $user_type);
            }
            break;

        case 'bid_accepted':
            $accepted_bid = $ride_request->bids->where('is_bid_accept', 1)->first();
            if ($accepted_bid) {
                $data['history_message'] = __('message.ride.bid_accept', ['name' => optional($accepted_bid->driver)->display_name]);
                $ride_request->update(['driver_id' => $accepted_bid->driver_id]);
                $history_data = [
                    'driver_id' => $accepted_bid->driver_id,
                    'driver_name' => optional($accepted_bid->driver)->display_name ?? '',
                ];
            }
            $sendTo = removeValueFromArray(['admin', 'driver'], $user_type);
            break;

        case 'bid_rejected':
            $rejected_bid = $ride_request->bids->where('is_bid_accept', 2)->first();
            if ($rejected_bid) {
                $data['history_message'] = __('message.ride.bid_reject', ['name' => optional($rejected_bid->driver)->display_name]);
                
                $current_rejected_ids = $ride_request->rejected_bid_driver_ids;
                if (is_string($current_rejected_ids)) {
                    $current_rejected_ids = json_decode($current_rejected_ids, true) ?? [];
                } elseif (!is_array($current_rejected_ids)) {
                    $current_rejected_ids = [];
                }
                
                if (!in_array($rejected_bid->driver_id, $current_rejected_ids)) {
                    $current_rejected_ids[] = $rejected_bid->driver_id; // Avoid duplicates
                }
                
                $ride_request->update(['rejected_bid_driver_ids' => json_encode($current_rejected_ids)]);
                
                $ride_request->update(['status' => 'bid_rejected']);
                
                $history_data = [
                    'driver_id' => $rejected_bid->driver_id,
                    'driver_name' => optional($rejected_bid->driver)->display_name ?? '',
                ];
                
                // sleep(2);
                
                // $ride_request->refresh();
                
                // if ($ride_request->status === 'bid_rejected') {
                //     $ride_request->update(['status' => 'new_ride_requested']);
                // }
            }
            $sendTo = removeValueFromArray(['admin', 'driver'], $user_type);
            break;
        case 'arriving':
            $data['history_message'] = __('message.ride.arriving');
            $history_data = [
                'driver_id' => $ride_request->driver_id,
                'driver_name' => optional($ride_request->driver)->display_name ?? '',
            ];
            // $mqtt_event = 'ride_request_status';
            $sendTo = removeValueFromArray(['admin', 'rider'], $user_type);
            break;

        case 'arrived':
            $data['history_message'] = __('message.ride.arrived');
            $history_data = [
                'driver_id' => $ride_request->driver_id,
                'driver_name' => optional($ride_request->driver)->display_name ?? '',
            ];
            // $mqtt_event = 'ride_request_status';
            $sendTo = removeValueFromArray(['admin', 'rider'], $user_type);
            break;
        
        // ride is in progress from the start to the end location
        case 'in_progress':
            $data['history_message'] = __('message.ride.in_progress');
            $history_data = [
                'driver_id' => $ride_request->driver_id,
                'driver_name' => optional($ride_request->driver)->display_name ?? '',
            ];
            // $mqtt_event = 'ride_request_status';
            $sendTo = removeValueFromArray(['admin', 'rider'], $user_type);
            break;
        
        case 'canceled':
            $data['history_message'] = __('message.ride.canceled');
            
            if( $ride_request->cancel_by == 'auto' ) {
                $history_data = [
                    'cancel_by' => $ride_request->cancel_by,
                    'rider_id' => $ride_request->rider_id,
                    'rider_name' => optional($ride_request->rider)->display_name ?? '',
                ];
            }

            if( $ride_request->cancel_by == 'rider' ) {
                $data['history_message'] = __('message.ride.rider_canceled');
                $history_data = [
                    'cancel_by' => $ride_request->cancel_by,
                    'rider_id' => $ride_request->rider_id,
                    'rider_name' => optional($ride_request->rider)->display_name ?? '',
                ];
            }

            if( $ride_request->cancel_by == 'driver' ) {
                $data['history_message'] = __('message.ride.driver_canceled');
                $history_data = [
                    'cancel_by' => $ride_request->cancel_by,
                    'driver_id' => $ride_request->driver_id,
                    'driver_name' => optional($ride_request->driver)->display_name ?? '',
                ];
            }
            
            if ($ride_request->driver_id) {
                $ride_request->driver->update(['is_available' => 1]);
            } elseif ($ride_request->riderequest_in_driver) {
                $ride_request->riderequest_in_driver->update(['is_available' => 1]);
            }
            
            // $mqtt_event = 'ride_request_status';
            $sendTo = removeValueFromArray(['admin', 'rider', 'driver'], $user_type);
            break;

        case 'driver_canceled':
            $data['history_message'] = __('message.ride.driver_canceled');
            $history_data = [
                'driver_id' => $ride_request->driver_id,
                'driver_name' => optional($ride_request->driver)->display_name ?? '',
            ];
            // $mqtt_event = 'ride_request_status';
            $sendTo = removeValueFromArray(['admin', 'rider'], $user_type);
            break;

        case 'rider_canceled':
            $data['history_message'] = __('message.ride.rider_canceled');
            $history_data = [
                'rider_id' => $ride_request->rider_id,
                'rider_name' => optional($ride_request->rider)->display_name ?? '',
            ];
            $sendTo = removeValueFromArray(['admin', 'driver'], $user_type);
            break;
        
        case 'completed':
            $data['history_message'] = __('message.ride.completed');
            $history_data = [
                'rider_id' => $ride_request->rider_id,
                'rider_name' => optional($ride_request->rider)->display_name ?? '',
            ];
            // $mqtt_event = 'ride_request_status';
            $sendTo = removeValueFromArray(['rider', 'driver'], $user_type);
            break;
        case 'payment_status_message':
            $data['history_message'] = __('message.ride.payment_status_message', ['id' => $ride_request->id, 'status' => __('message.'.optional($ride_request->payment)->payment_status) ]);
            $history_data = [
                'rider_id' => $ride_request->rider_id,
                'status' => optional($ride_request->payment)->payment_status,
                'rider_name' => optional($ride_request->rider)->display_name ?? '',
            ];
            $sendTo = removeValueFromArray(['admin', 'driver', 'rider'], $user_type);
            // $mqtt_event = 'ride_request_status';
            break;

        
            
        default:
            # code...
            break;
    }

    $data['history_data'] = json_encode($history_data);

    if( $data['history_type'] != null ) {
        RideRequestHistory::create($data);
    }

    if( count($sendTo) > 0 ) {
        $notification_data = [
            'id'   => $ride_request->id,
            'type' => $data['history_type'],
            'subject' => __('message.'.$data['history_type']),
            'message' => $data['history_message'],
            'rider_id' => $ride_request->rider_id,
            'driver_id' => $ride_request->driver_id,
        ];
    
        $notify_data = new \stdClass();
        $notify_data->success = true;
        $notify_data->success_type = $data['history_type'];
        $notify_data->success_message = $data['history_message'];
        $notify_data->result = new RideRequestResource($ride_request);
        foreach($sendTo as $send){
            switch ($send) {
                case 'admin':
                    $user = User::admin();
                    break;
                case 'rider':
                    $user = User::whereId( $ride_request->rider_id )->first();
                    break;
                case 'driver':
                    $user = User::whereId( $ride_request->driver_id )->first();
                    break;
            }

            if ($user != null) {
                if ($send != 'driver') {
                    if ($data['history_type'] != 'new_ride_requested' && $data['history_type'] != 'scheduled' && $data['history_type'] != 'driver_accepted') {
                        try {
                            $document_name = 'ride_' . $ride_request->id;
                            $firebaseData = app('firebase.firestore')->database()->collection('rides')->document($document_name);
                        
                            $nearby_driver_ids = $ride_request->nearby_driver_ids;
                            if (is_string($nearby_driver_ids)) {
                                $nearby_driver_ids = json_decode($nearby_driver_ids, true);
                            } elseif (is_object($nearby_driver_ids)) {
                                $nearby_driver_ids = (array)$nearby_driver_ids;
                            }
                            $nearby_driver_ids = is_array($nearby_driver_ids) ? $nearby_driver_ids : [];
                        
                            $rejected_bid_driver_ids = is_string($ride_request->rejected_bid_driver_ids) 
                                ? json_decode($ride_request->rejected_bid_driver_ids, true) 
                                : [];
                            $rejected_bid_driver_ids = is_array($rejected_bid_driver_ids) ? array_filter($rejected_bid_driver_ids) : [];
                        
                            $updated_nearby_driver_ids = !empty($nearby_driver_ids) && !empty($rejected_bid_driver_ids) 
                                ? array_diff($nearby_driver_ids, $rejected_bid_driver_ids) 
                                : $nearby_driver_ids;
                        
                            $rideData = [
                                'on_rider_stream_api_call' => 1,
                                'on_stream_api_call' => 1,
                                'ride_id' => $ride_request->id,
                                'rider_id' => $ride_request->rider_id,
                                'status' => $ride_request->status,
                                'ride_has_bid' => $ride_request->ride_has_bid,
                                'driver_ids' => $updated_nearby_driver_ids
                            ];
                        
                            $rideRequestStatuses = ['bid_accepted', 'arrived', 'in_progress', 'completed', 'accepted', 'arriving'];
                            if (in_array($data['history_type'], $rideRequestStatuses) || in_array($ride_request->status, $rideRequestStatuses)) {
                                $rideData['driver_ids'] = [$ride_request->driver_id];
                            }
                        
                            if ($data['history_type'] == 'bid_rejected') {
                                $rideData['status'] = 'bid_rejected';
                            }
                        
                            $firebaseData->set($rideData, ['merge' => true]);
                        
                            if ($ride_request->status == 'completed') {
                                $rideData['payment_status'] = $ride_request->payment->payment_status ?? '';
                                $rideData['payment_type'] = $ride_request->payment->payment_type ?? '';
                                $rideData['tips'] = $ride_request->tips ? 1 : 0;
                        
                                $firebaseData->set($rideData, ['merge' => true]);
                        
                                if ($ride_request->payment->payment_status === 'paid') {
                                    sleep(3);
                                    $firebaseData->delete();
                                }
                            } elseif ($data['history_type'] === 'canceled' || $ride_request->status === 'canceled') {
                                sleep(3);
                                $rideData['payment_status'] = 'canceled';
                                $firebaseData->set($rideData, ['merge' => true]);
                                //$firebaseData->delete();
                            } else {
                                $rideData['payment_status'] = '';
                                $rideData['payment_type'] = '';
                                $rideData['tips'] = 0;
                                $firebaseData->set($rideData);
                            }
                        } catch (\Exception $e) {
                            \Log::error('Error updating Firestore document for Ride: ' . $e->getMessage());
                            \Log::error('Error context: ride_id=' . $ride_request->id . ' | rideData=' . json_encode($rideData));
                        }
                        
                    }
                    $user->notify(new RideNotification($notification_data)); 
                }
                $user->notify(new CommonNotification($notification_data['type'], $notification_data));
            }

            if( $user == null && isset($ride_request->riderequest_in_driver_id) && $ride_request->riderequest_in_driver != null && $data['history_type'] == 'canceled' ) {
                $ride_request->riderequest_in_driver->notify(new CommonNotification($notification_data['type'], $notification_data));
                $ride_request->update([
                    'riderequest_in_driver_id' => null,
                    'riderequest_in_datetime' => null
                ]);
            }
        }
    }
}

function checkMenuRoleAndPermission($menu)
{
    if (!auth()->check()) {
        return false;
    }

    $user = auth()->user();
    $roles = $menu->data('role');
    $permissions = $menu->data('permission');

    // Case: No role or permission set, but user is admin → allow
    if (!$roles && $user->hasRole('admin')) {
        return true;
    }

    // Case: No role or permission set at all → allow
    if (!$roles && !$permissions) {
        return true;
    }

    // Handle roles (supports comma-separated or array)
    if ($roles) {
        $rolesArray = is_array($roles) ? $roles : explode(',', $roles);
        if ($user->hasAnyRole($rolesArray)) {
            return true;
        }
    }

    // Handle permissions (supports comma-separated or array)
    if ($permissions) {
        $permArray = is_array($permissions) ? $permissions : explode(',', $permissions);
        foreach ($permArray as $perm) {
            if ($user->can(trim($perm))) {
                return true;
            }
        }
    }

    return false;
}


function checkRolePermission($role,$permission){
    try{
        if($role->hasPermissionTo($permission)){
            return true;
        }
        return false;
    }catch (Exception $e){
        return false;
    }
}

function getSingleMedia($model, $collection = 'profile_image', $skip=true   )
{
    if (!auth()->check() && $skip) {
        return asset('images/user/1.jpg');
    }
    $media = null;
    if ($model !== null) {
        $media = $model->getFirstMedia($collection);
    }

    if (getFileExistsCheck($media))
    {
        return $media->getFullUrl();
    } else {
        switch ($collection) {
            case 'profile_image':
                $media = asset('images/user/1.jpg');
                break;
            case 'site_logo':
                $media = asset('images/logo.png');
                break;
            case 'site_dark_logo':
                $media = asset('images/dark_logo.png');
                break;
            case 'gateway_image':
                $gateway_name = $model->type ?? 'default';
                $media = asset('images/'.$gateway_name.'.png');
                break;
            case 'site_favicon':
                $media = asset('images/favicon.ico');
                break;
            default:
                $media = asset('images/default.png');
                break;
        }
        return $media;
    }
}

function getFileExistsCheck($media)
{
    $mediaCondition = false;

    if($media) {
        if($media->disk == 'public') {
            $mediaCondition = file_exists($media->getPath());
        } else {
            $mediaCondition = Storage::disk($media->disk)->exists($media->getPath());
        }
    }
    return $mediaCondition;
}

function uploadMediaFile($model,$file,$name)
{
    if($file) {
        $model->clearMediaCollection($name);
        if (is_array($file)){
            foreach ($file as $key => $value){
                $model->addMedia($value)->toMediaCollection($name);
            }
        }else{
            $model->addMedia($file)->toMediaCollection($name);
        }
    }

    return true;
}

function getAttachments($attchments)
{
    $files = [];
    if (count($attchments) > 0) {
        foreach ($attchments as $attchment) {
            if (getFileExistsCheck($attchment)) {
                array_push($files, $attchment->getFullUrl());
            }
        }
    }

    return $files;
}

function getMediaFileExit($model, $collection = 'profile_image')
{
    if($model==null){
        return asset('images/user/1.jpg');
    }

    $media = $model->getFirstMedia($collection);

    return getFileExistsCheck($media);
}

function couponVerifyResponse($status) {
    switch ($status) {
        case 405:
            $message = __('message.coupons.expire');
            break;
        case 406:
            $message = __('message.coupons.first_rider_only');
            break;
        case 407:
            $message = __('message.coupons.applied_limit');
            break;
        case 400:
            $message = __('message.coupons.code_invalid');
            break;
        default:
            $message = __('message.coupons.code_not_found');
            break;
    }
    $response = [
        'message' => $status == 200 ? __('message.coupons.code_valid') : $message,
    ];

    return $response;
}

function removeValueFromArray($array = [], $find = null)
{
    foreach (array_keys($array, $find) as $key) {
        unset($array[$key]);
    }

    return array_values($array);
}

function timeZoneList()
{
    $list = \DateTimeZone::listAbbreviations();
    $idents = \DateTimeZone::listIdentifiers();

    $data = $offset = $added = array();
    foreach ($list as $abbr => $info) {
        foreach ($info as $zone) {
            if (!empty($zone['timezone_id']) and !in_array($zone['timezone_id'], $added) and in_array($zone['timezone_id'], $idents)) {

                $z = new \DateTimeZone($zone['timezone_id']);
                $c = new \DateTime(null, $z);
                $zone['time'] = $c->format('H:i a');
                $offset[] = $zone['offset'] = $z->getOffset($c);
                $data[] = $zone;
                $added[] = $zone['timezone_id'];
            }
        }
    }

    array_multisort($offset, SORT_ASC, $data);
    $options = array();
    foreach ($data as $key => $row) {
        $options[$row['timezone_id']] = $row['time'] . ' - ' . formatOffset($row['offset'])  . ' ' . $row['timezone_id'];
    }
    return $options;
}

function formatOffset($offset)
{
    $hours = $offset / 3600;
    $remainder = $offset % 3600;
    $sign = $hours > 0 ? '+' : '-';
    $hour = (int) abs($hours);
    $minutes = (int) abs($remainder / 60);

    if ($hour == 0 and $minutes == 0) {
        $sign = ' ';
    }
    return 'GMT' . $sign . str_pad($hour, 2, '0', STR_PAD_LEFT) . ':' . str_pad($minutes, 2, '0');
}
function languagesArray($ids = [])
{
    $language = [
        [ 'title' => 'Abkhaz' , 'id' => 'ab'],
        [ 'title' => 'Afar' , 'id' => 'aa'],
        [ 'title' => 'Afrikaans' , 'id' => 'af'],
        [ 'title' => 'Akan' , 'id' => 'ak'],
        [ 'title' => 'Albanian' , 'id' => 'sq'],
        [ 'title' => 'Amharic' , 'id' => 'am'],
        [ 'title' => 'Arabic' , 'id' => 'ar'],
        [ 'title' => 'Aragonese' , 'id' => 'an'],
        [ 'title' => 'Armenian' , 'id' => 'hy'],
        [ 'title' => 'Assamese' , 'id' => 'as'],
        [ 'title' => 'Avaric' , 'id' => 'av'],
        [ 'title' => 'Avestan' , 'id' => 'ae'],
        [ 'title' => 'Aymara' , 'id' => 'ay'],
        [ 'title' => 'Azerbaijani' , 'id' => 'az'],
        [ 'title' => 'Bambara' , 'id' => 'bm'],
        [ 'title' => 'Bashkir' , 'id' => 'ba'],
        [ 'title' => 'Basque' , 'id' => 'eu'],
        [ 'title' => 'Belarusian' , 'id' => 'be'],
        [ 'title' => 'Bengali' , 'id' => 'bn'],
        [ 'title' => 'Bihari' , 'id' => 'bh'],
        [ 'title' => 'Bislama' , 'id' => 'bi'],
        [ 'title' => 'Bosnian' , 'id' => 'bs'],
        [ 'title' => 'Breton' , 'id' => 'br'],
        [ 'title' => 'Bulgarian' , 'id' => 'bg'],
        [ 'title' => 'Burmese' , 'id' => 'my'],
        [ 'title' => 'Catalan; Valencian' , 'id' => 'ca'],
        [ 'title' => 'Chamorro' , 'id' => 'ch'],
        [ 'title' => 'Chechen' , 'id' => 'ce'],
        [ 'title' => 'Chichewa; Chewa; Nyanja' , 'id' => 'ny'],
        [ 'title' => 'Chinese' , 'id' => 'zh'],
        [ 'title' => 'Chuvash' , 'id' => 'cv'],
        [ 'title' => 'Cornish' , 'id' => 'kw'],
        [ 'title' => 'Corsican' , 'id' => 'co'],
        [ 'title' => 'Cree' , 'id' => 'cr'],
        [ 'title' => 'Croatian' , 'id' => 'hr'],
        [ 'title' => 'Czech' , 'id' => 'cs'],
        [ 'title' => 'Danish' , 'id' => 'da'],
        [ 'title' => 'Divehi; Dhivehi; Maldivian;' , 'id' => 'dv'],
        [ 'title' => 'Dutch' , 'id' => 'nl'],
        [ 'title' => 'English' , 'id' => 'en'],
        [ 'title' => 'Esperanto' , 'id' => 'eo'],
        [ 'title' => 'Estonian' , 'id' => 'et'],
        [ 'title' => 'Ewe' , 'id' => 'ee'],
        [ 'title' => 'Faroese' , 'id' => 'fo'],
        [ 'title' => 'Fijian' , 'id' => 'fj'],
        [ 'title' => 'Finnish' , 'id' => 'fi'],
        [ 'title' => 'French' , 'id' => 'fr'],
        [ 'title' => 'Fula; Fulah; Pulaar; Pular' , 'id' => 'ff'],
        [ 'title' => 'Galician' , 'id' => 'gl'],
        [ 'title' => 'Georgian' , 'id' => 'ka'],
        [ 'title' => 'German' , 'id' => 'de'],
        [ 'title' => 'Greek, Modern' , 'id' => 'el'],
        [ 'title' => 'Guaraní' , 'id' => 'gn'],
        [ 'title' => 'Gujarati' , 'id' => 'gu'],
        [ 'title' => 'Haitian; Haitian Creole' , 'id' => 'ht'],
        [ 'title' => 'Hausa' , 'id' => 'ha'],
        [ 'title' => 'Hebrew (modern)' , 'id' => 'he'],
        [ 'title' => 'Herero' , 'id' => 'hz'],
        [ 'title' => 'Hindi' , 'id' => 'hi'],
        [ 'title' => 'Hiri Motu' , 'id' => 'ho'],
        [ 'title' => 'Hungarian' , 'id' => 'hu'],
        [ 'title' => 'Interlingua' , 'id' => 'ia'],
        [ 'title' => 'Indonesian' , 'id' => 'id'],
        [ 'title' => 'Interlingue' , 'id' => 'ie'],
        [ 'title' => 'Irish' , 'id' => 'ga'],
        [ 'title' => 'Igbo' , 'id' => 'ig'],
        [ 'title' => 'Inupiaq' , 'id' => 'ik'],
        [ 'title' => 'Ido' , 'id' => 'io'],
        [ 'title' => 'Icelandic' , 'id' => 'is'],
        [ 'title' => 'Italian' , 'id' => 'it'],
        [ 'title' => 'Inuktitut' , 'id' => 'iu'],
        [ 'title' => 'Japanese' , 'id' => 'ja'],
        [ 'title' => 'Javanese' , 'id' => 'jv'],
        [ 'title' => 'Kalaallisut, Greenlandic' , 'id' => 'kl'],
        [ 'title' => 'Kannada' , 'id' => 'kn'],
        [ 'title' => 'Kanuri' , 'id' => 'kr'],
        [ 'title' => 'Kashmiri' , 'id' => 'ks'],
        [ 'title' => 'Kazakh' , 'id' => 'kk'],
        [ 'title' => 'Khmer' , 'id' => 'km'],
        [ 'title' => 'Kikuyu, Gikuyu' , 'id' => 'ki'],
        [ 'title' => 'Kinyarwanda' , 'id' => 'rw'],
        [ 'title' => 'Kirghiz, Kyrgyz' , 'id' => 'ky'],
        [ 'title' => 'Komi' , 'id' => 'kv'],
        [ 'title' => 'Kongo' , 'id' => 'kg'],
        [ 'title' => 'Korean' , 'id' => 'ko'],
        [ 'title' => 'Kurdish' , 'id' => 'ku'],
        [ 'title' => 'Kwanyama, Kuanyama' , 'id' => 'kj'],
        [ 'title' => 'Latin' , 'id' => 'la'],
        [ 'title' => 'Luxembourgish, Letzeburgesch' , 'id' => 'lb'],
        [ 'title' => 'Luganda' , 'id' => 'lg'],
        [ 'title' => 'Limburgish, Limburgan, Limburger' , 'id' => 'li'],
        [ 'title' => 'Lingala' , 'id' => 'ln'],
        [ 'title' => 'Lao' , 'id' => 'lo'],
        [ 'title' => 'Lithuanian' , 'id' => 'lt'],
        [ 'title' => 'Luba-Katanga' , 'id' => 'lu'],
        [ 'title' => 'Latvian' , 'id' => 'lv'],
        [ 'title' => 'Manx' , 'id' => 'gv'],
        [ 'title' => 'Macedonian' , 'id' => 'mk'],
        [ 'title' => 'Malagasy' , 'id' => 'mg'],
        [ 'title' => 'Malay' , 'id' => 'ms'],
        [ 'title' => 'Malayalam' , 'id' => 'ml'],
        [ 'title' => 'Maltese' , 'id' => 'mt'],
        [ 'title' => 'Māori' , 'id' => 'mi'],
        [ 'title' => 'Marathi (Marāṭhī)' , 'id' => 'mr'],
        [ 'title' => 'Marshallese' , 'id' => 'mh'],
        [ 'title' => 'Mongolian' , 'id' => 'mn'],
        [ 'title' => 'Nauru' , 'id' => 'na'],
        [ 'title' => 'Navajo, Navaho' , 'id' => 'nv'],
        [ 'title' => 'Norwegian Bokmål' , 'id' => 'nb'],
        [ 'title' => 'North Ndebele' , 'id' => 'nd'],
        [ 'title' => 'Nepali' , 'id' => 'ne'],
        [ 'title' => 'Ndonga' , 'id' => 'ng'],
        [ 'title' => 'Norwegian Nynorsk' , 'id' => 'nn'],
        [ 'title' => 'Norwegian' , 'id' => 'no'],
        [ 'title' => 'Nuosu' , 'id' => 'ii'],
        [ 'title' => 'South Ndebele' , 'id' => 'nr'],
        [ 'title' => 'Occitan' , 'id' => 'oc'],
        [ 'title' => 'Ojibwe, Ojibwa' , 'id' => 'oj'],
        [ 'title' => 'Oromo' , 'id' => 'om'],
        [ 'title' => 'Oriya' , 'id' => 'or'],
        [ 'title' => 'Ossetian, Ossetic' , 'id' => 'os'],
        [ 'title' => 'Panjabi, Punjabi' , 'id' => 'pa'],
        [ 'title' => 'Pāli' , 'id' => 'pi'],
        [ 'title' => 'Persian' , 'id' => 'fa'],
        [ 'title' => 'Polish' , 'id' => 'pl'],
        [ 'title' => 'Pashto, Pushto' , 'id' => 'ps'],
        [ 'title' => 'Portuguese' , 'id' => 'pt'],
        [ 'title' => 'Quechua' , 'id' => 'qu'],
        [ 'title' => 'Romansh' , 'id' => 'rm'],
        [ 'title' => 'Kirundi' , 'id' => 'rn'],
        [ 'title' => 'Romanian, Moldavian, Moldovan' , 'id' => 'ro'],
        [ 'title' => 'Russian' , 'id' => 'ru'],
        [ 'title' => 'Sanskrit (Saṁskṛta)' , 'id' => 'sa'],
        [ 'title' => 'Sardinian' , 'id' => 'sc'],
        [ 'title' => 'Sindhi' , 'id' => 'sd'],
        [ 'title' => 'Northern Sami' , 'id' => 'se'],
        [ 'title' => 'Samoan' , 'id' => 'sm'],
        [ 'title' => 'Sango' , 'id' => 'sg'],
        [ 'title' => 'Serbian' , 'id' => 'sr'],
        [ 'title' => 'Scottish Gaelic; Gaelic' , 'id' => 'gd'],
        [ 'title' => 'Shona' , 'id' => 'sn'],
        [ 'title' => 'Sinhala, Sinhalese' , 'id' => 'si'],
        [ 'title' => 'Slovak' , 'id' => 'sk'],
        [ 'title' => 'Slovene' , 'id' => 'sl'],
        [ 'title' => 'Somali' , 'id' => 'so'],
        [ 'title' => 'Southern Sotho' , 'id' => 'st'],
        [ 'title' => 'Spanish; Castilian' , 'id' => 'es'],
        [ 'title' => 'Sundanese' , 'id' => 'su'],
        [ 'title' => 'Swahili' , 'id' => 'sw'],
        [ 'title' => 'Swati' , 'id' => 'ss'],
        [ 'title' => 'Swedish' , 'id' => 'sv'],
        [ 'title' => 'Tamil' , 'id' => 'ta'],
        [ 'title' => 'Telugu' , 'id' => 'te'],
        [ 'title' => 'Tajik' , 'id' => 'tg'],
        [ 'title' => 'Thai' , 'id' => 'th'],
        [ 'title' => 'Tigrinya' , 'id' => 'ti'],
        [ 'title' => 'Tibetan Standard, Tibetan, Central' , 'id' => 'bo'],
        [ 'title' => 'Turkmen' , 'id' => 'tk'],
        [ 'title' => 'Tagalog' , 'id' => 'tl'],
        [ 'title' => 'Tswana' , 'id' => 'tn'],
        [ 'title' => 'Tonga (Tonga Islands)' , 'id' => 'to'],
        [ 'title' => 'Turkish' , 'id' => 'tr'],
        [ 'title' => 'Tsonga' , 'id' => 'ts'],
        [ 'title' => 'Tatar' , 'id' => 'tt'],
        [ 'title' => 'Twi' , 'id' => 'tw'],
        [ 'title' => 'Tahitian' , 'id' => 'ty'],
        [ 'title' => 'Uighur, Uyghur' , 'id' => 'ug'],
        [ 'title' => 'Ukrainian' , 'id' => 'uk'],
        [ 'title' => 'Urdu' , 'id' => 'ur'],
        [ 'title' => 'Uzbek' , 'id' => 'uz'],
        [ 'title' => 'Venda' , 'id' => 've'],
        [ 'title' => 'Vietnamese' , 'id' => 'vi'],
        [ 'title' => 'Volapük' , 'id' => 'vo'],
        [ 'title' => 'Walloon' , 'id' => 'wa'],
        [ 'title' => 'Welsh' , 'id' => 'cy'],
        [ 'title' => 'Wolof' , 'id' => 'wo'],
        [ 'title' => 'Western Frisian' , 'id' => 'fy'],
        [ 'title' => 'Xhosa' , 'id' => 'xh'],
        [ 'title' => 'Yiddish' , 'id' => 'yi'],
        [ 'title' => 'Yoruba' , 'id' => 'yo'],
        [ 'title' => 'Zhuang, Chuang' , 'id' => 'za']
    ];

    if(!empty($ids))
    {
        $language = collect($language)->whereIn('id',$ids)->values();
    }

    return $language;
}

function flattenToMultiDimensional(array $array, $delimiter = '.')
{
    $result = [];
    foreach ($array as $notations => $value) {
        // extract keys
        $keys = explode($delimiter, $notations);
        // reverse keys for assignments
        $keys = array_reverse($keys);

        // set initial value
        $lastVal = $value;
        foreach ($keys as $key) {
            // wrap value with key over each iteration
            $lastVal = [
                $key => $lastVal
            ];
        }
        // merge result
        $result = array_merge_recursive($result, $lastVal);
    }
    return $result;
}

function createLangFile($lang=''){
    $langDir = resource_path().'/lang/';
    $enDir = $langDir.'en';
    $currentLang = $langDir . $lang;
    if(!File::exists($currentLang)){
       File::makeDirectory($currentLang);
       File::copyDirectory($enDir,$currentLang);
    }
}

function dateAgoFormate($date,$type2='')
{
    if($date == null || $date == '0000-00-00 00:00:00') {
        return '-';
    }

    $diff_time1 = \Carbon\Carbon::createFromTimeStamp(strtotime($date))->diffForHumans();
    $datetime = new \DateTime($date);
    $la_time = new \DateTimeZone(auth()->check() ? auth()->user()->timezone ?? 'UTC' : 'UTC');
    $datetime->setTimezone($la_time);
    $diff_date = $datetime->format('Y-m-d H:i:s');

    $diff_time = \Carbon\Carbon::parse($diff_date)->isoFormat('LLL');

    if($type2 != ''){
        return $diff_time;
    }

    return $diff_time1 .' on '.$diff_time;
}

function timeAgoFormate($date)
{
    if($date==null){
        return '-';
    }

    date_default_timezone_set('UTC');

    $diff_time= \Carbon\Carbon::createFromTimeStamp(strtotime($date))->diffForHumans();

    return $diff_time;
}

function envChanges($type,$value)
{
    $path = base_path('.env');

    $checkType = $type.'="';
    if(strpos($value,' ') || strpos(file_get_contents($path),$checkType) || preg_match('/[\'^£$%&*()}{@#~?><>,|=_+¬-]/', $value)){
        $value = '"'.$value.'"';
    }

    $value = str_replace('\\', '\\\\', $value);

    if (file_exists($path)) {
        $typeValue = env($type);

        if(strpos(env($type),' ') || strpos(file_get_contents($path),$checkType)){
            $typeValue = '"'.env($type).'"';
        }

        file_put_contents($path, str_replace(
            $type.'='.$typeValue, $type.'='.$value, file_get_contents($path)
        ));

        $onesignal = collect(config('constant.ONESIGNAL'))->keys();

        $checkArray = Arr::collapse([['DEFAULT_LANGUAGE']]);


        if( in_array( $type ,$checkArray) ){
            if(env($type) === null){
                file_put_contents($path,"\n".$type.'='.$value ,FILE_APPEND);
            }
        }
    }
}
function convertUnitvalue($unit)
{
    switch ($unit) {
        case 'mile':
            return 3956;
            break;
        default:
            return 6371;
            break;
    }
}

function mile_to_km($mile) {
    return $mile * 1.60934;
}

function km_to_mile($km) {
    return $km * 0.621371;
}

function og_get_distance_matrix_result($pick_lat, $pick_lng, $drop_lat, $drop_lng, $drop_location) {
    
    $result = collect($drop_location)->map(function ($item, $index) use ($pick_lng, $drop_lat, $drop_lng, $drop_location) {
        if ($index == 0) {
            return $pick_latlong . '|' . $item['latitude'] . ',' . $item['longitude'];
        } elseif (count($drop_location) == $index) {
            return $drop_location[$index - 1]['latitude'] . ',' . $drop_location[$index - 1]['longitude'] . '|' . $drop_lat;
        } else {
            return $drop_location[$index - 1]['latitude'] . ',' . $drop_location[$index - 1]['longitude'] . '|' . $item['latitude'] . ',' . $item['longitude'];
        }
    });

}

function og_get_distance_matrix($pick_lat, $pick_lng, $drop_lat, $drop_lng, $traffic = false) {
    $google_map_api_key = env('GOOGLE_MAP_KEY');
        
    $response = Http::withHeaders([
        'Accept-Language' => request('language'),
    ])->get('https://maps.googleapis.com/maps/api/distancematrix/json?origins='.$pick_lat.','.$pick_lng.'&destinations='.$drop_lat.','.$drop_lng.'&key='.$google_map_api_key.'&mode=driving');
    
    $responses = $response->json();

    return $responses;
}

function distance_value_from_distance_matrix($distance_matrix) {
    $element = first_element_in_distance_matrix($distance_matrix);

    if (isset($element) && isset($element['distance'])) {
        return (float)$element['distance']['value'];
    }

    return null;
}

function duration_value_from_distance_matrix($distance_matrix)
{
    $element = first_element_in_distance_matrix($distance_matrix);

    if (isset($element)) {
        if (isset($element['duration_in_traffic'])) {
            return (int)$element['duration_in_traffic']['value'];
        } elseif (isset($element['duration'])) {
            return (int)$element['duration']['value'];
        }
    }
}

function first_element_in_distance_matrix($distance_matrix)
{
    if (!is_array($distance_matrix['rows']) || empty($distance_matrix['rows'])) {
        return null;
    }
    $row = $distance_matrix['rows'][0];
    if (!is_array($row['elements']) || empty($row['elements'])) {
        return null;
    }
    return $row['elements'][0];
}

function calculateRideFares($distance_in_unit, $pickupLat, $pickupLng, $dropLat, $dropLng, $multiLocation, $dropoff_time_in_seconds, $service, $coupon = null, $surge_price = null,$ride_datetime = null,$is_credit_used=false, $rider_id=null) {
    $time_price = 0;

    $distance_unit = $service['distance_unit'] ?? 'km';
    $minimum_distance = $service['minimum_distance'] ?? 0;

    if ($distance_unit == 'mile') {
        $distance_in_unit = km_to_mile($distance_in_unit); // convert km distance to miles
    }

    // We are using google map api to calculate distance
    // $previousLat = $pickupLat;
    // $previousLng = $pickupLng;

    // foreach ($multiLocation as $location) {
    //     $currentLat = $location['lat'];
    //     $currentLng = $location['lng'];
    //     $distance = haversineDistance($previousLat, $previousLng, $currentLat, $currentLng);        
    //     $distance_in_unit += $distance;
    //     $previousLat = $currentLat;
    //     $previousLng = $currentLng;
    // }

    // $finalDistance = haversineDistance($previousLat, $previousLng, $dropLat, $dropLng);

    // $distance_in_unit += $finalDistance;

    // if ($distance_unit == 'mile') {
    //     $distance_in_unit = km_to_mile($distance_in_unit);
    // }

    $base_fare = $service['base_fare'];
    //$time_price = ($dropoff_time_in_seconds / 60) * $service['per_minute_drive'];

    $duration = $dropoff_time_in_seconds / 60;

    // Time Fare
    if ($duration <= $distance_in_unit) {
        // Short duration ride
        $time_price = $duration * $service['time_fare_short_ride'];
    } elseif ($duration > $distance_in_unit && $duration <= 2 * $distance_in_unit) {
        // Moderate duration ride
        $time_price = $duration * $service['time_fare_moderate_ride'];
    } elseif ($duration > 2 * $distance_in_unit) {
        // Long duration / heavy traffic
        $time_price = $duration * $service['time_fare_long_ride'];
    } else {
        $time_price = 0;
    }

    if ($distance_in_unit > $minimum_distance) {
        $distance_in_unit -= $minimum_distance;
    }else{
        // If the distance_in_unit is less than the minimum distance, we keep the distance as 0
        // because the base fare already covers up to the minimum distance.
        $distance_in_unit = 0;
    }

    $distance_price = ($distance_in_unit * $service['per_distance']); // Distance Fare    

    $base_and_distance_price = ($base_fare + $distance_price);
    $total_amount = $base_and_distance_price + $time_price; // Total ride fare. We are not including time idling in the fare calculation, as it depends on the waiting time. For estimation purposes, the time idling fare is not considered.

    // Company fee applicable on total ride fare
    if($total_amount < $service['company_fee_threshold']){
        $company_fee = $service['company_fee_below_threshold'];
    }else{
        $company_fee = $service['company_fee_above_threshold'];
    }

    $total_amount += $company_fee; // Normal Ride Fare

    // Expenses
    $expenses = $total_amount * $service['expenses']/100;

    // Total Ride Fee (what rider pays)
    $total_amount += $expenses;

    if ($total_amount < $service['minimum_fare']) {
        $total_amount = $service['minimum_fare'];
    }

    // if ($service['commission_type'] == 'fixed') {
    //     $commission = $service['admin_commission'] + $service['fleet_commission'];
    //     if ($total_amount <= $commission) {
    //         $total_amount += $commission;
    //     }
    // }

    $discount_amount = 0;
    $subtotal = $total_amount;

    if ($coupon) {
        $coupon = is_array($coupon) ? (object)$coupon : $coupon;
        if ($coupon->minimum_amount < $total_amount) {
            if ($coupon->discount_type == 'percentage') {
                $discount_amount = $total_amount * ($coupon->discount / 100);
            } else {
                $discount_amount = $coupon->discount;
            }

            if ($coupon->maximum_discount > 0 && $discount_amount > $coupon->maximum_discount) {
                $discount_amount = $coupon->maximum_discount;
            }
            $subtotal = $total_amount - $discount_amount;
        } else {
            if (!empty(request('service_id')) && $service['id'] == request('service_id')) {
                $response = [
                    'message' => 'Minimum fare ₹'.$coupon->minimum_amount.' required.',
                ];
                throw new \Illuminate\Http\Exceptions\HttpResponseException(json_custom_response($response, 400));
            }
        }
    }

    // use wallet balance for ride booking
    if($is_credit_used){
        $user_wallet = Wallet::where([ 'user_id' => $rider_id ])->first();
        if($user_wallet && $user_wallet->total_amount > 0){ 
            $subtotal = $total_amount;    
            if ($user_wallet->total_amount >= $subtotal) {
                // Wallet has enough to cover the subtotal
                $credit_used = $subtotal;
                $available_wallet_amount = $user_wallet->total_amount - $credit_used;
                $subtotal = 0; // Fully covered by wallet
            } else {
                // Wallet doesn't have enough, use all wallet balance
                $credit_used = $user_wallet->total_amount;
                $available_wallet_amount = 0;
                $subtotal = $subtotal - $credit_used;
            }
        }
    }

    $surge_amount = 0;
    $surge_price_setting_value = SettingData('ride', 'surge_price') ?? null;
    if ($surge_price_setting_value == 1 && isset($surge_price) && (is_object($surge_price) || is_array($surge_price))) {

        $timezone = $service?->region?->timezone ?? $service?->timezone ?? 'UTC';
        $rideTimeOnly = \Carbon\Carbon::createFromFormat('Y-m-d H:i', $ride_datetime, $timezone)->format('H:i');
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
                $total_amount += $surge_amount;
                break;
            }
        }
    }

    $final_subtotal = $subtotal + ($surge_amount ? $surge_amount : 0);
    $driver_earning = $final_subtotal - $company_fee - $expenses;

    return [
        'distance' => round($distance_in_unit, 2),
        'minimum_distance_in_km' => $minimum_distance,
        'distance_price' => (float) number_format( (float) $distance_price, 2,'.',''),
        'time_price' => (float) number_format( (float) $time_price, 2,'.',''),
        'total_amount' => (float) number_format( (float) $total_amount, 2,'.',''),
        'subtotal' => (float) number_format( (float) $final_subtotal, 2,'.',''),
        'discount_amount' => $discount_amount,
        'fixed_charge' => (float) number_format( (float) $surge_amount, 2,'.',''),
        'credit_used' => $credit_used ?? 0,
        'available_wallet_amount' => $available_wallet_amount ?? 0,
        'company_fee_charge' => (float) number_format( (float) $company_fee, 2,'.',''),
        'expenses_charge' => (float) number_format( (float) $expenses, 2,'.',''),
        'driver_earning' => (float) number_format( (float) $driver_earning, 2,'.',''),
    ];
}


function haversineDistance($lat1, $lng1, $lat2, $lng2) {
    $earthRadius = 6371;

    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);

    $a = sin($dLat / 2) * sin($dLat / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * 
         sin($dLng / 2) * sin($dLng / 2);

    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    $distance = $earthRadius * $c;
    return $distance;
}



function calculateRideDuration($start_time, $current_time = null)
{
    $current_time = $current_time ?? date('Y-m-d H:i:s');
    $start_time = Carbon\Carbon::parse($start_time);
    $end_time = Carbon\Carbon::parse($current_time);
    $total_duration = $end_time->diffInMinutes($start_time);

    return $total_duration;
}

function calculate_distance($lat1, $lng1, $lat2, $lng2, $unit)
{
    if (($lat1 == $lat2) && ($lng1 == $lng2)) {
        return 0;
    } else {
        $theta = $lng1 - $lng2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;

        if ($unit == "km") {
            return ($miles * 1.609344);
        } elseif ($unit == "mile") {
            return ($miles * 0.8684);
        } else {
            return $miles;
        }
    }
}

function SettingData($type, $key = null)
{
    $setting = Setting::where('type',$type);
   
    $setting->when($key != null, function ($q) use($key) {
        return $q->where('key', $key);
    });

    $setting_data = $setting->pluck('value')->first();
   return $setting_data;
}

function getPriceFormat($price)
{
    if (gettype($price) == 'string') {
        return $price;
    }
    if($price === null){
        $price = 0;
    }
    
    $currency_code = SettingData('CURRENCY', 'CURRENCY_CODE') ?? 'USD';
    $currecy = currencyArray($currency_code);

    $code = $currecy['symbol'] ?? '$';
    $position = SettingData('CURRENCY', 'CURRENCY_POSITION') ?? 'left';
    
    if ($position == 'left') {
        $price = $code."".number_format( (float) $price,2,'.','');
    } else {
        $price = number_format( (float) $price, 2,'.','')."".$code;
    }

    return $price;
}

function getPointFormat($points)
{
    if (gettype($points) == 'string') {
        return $points;
    }
    if($points === null){
        $points = 0;
    }
    
    $points = number_format( (float) $points, 2,'.','');

    return $points;
}

function verify_coupon_code($coupon_code)
{
    $coupon = Coupon::where('code', $coupon_code)->where('status',1)->first();
    $status = isset($coupon) ? 400 : 200;
    if($coupon != null) {
        $status = Coupon::isValidCoupon($coupon);
    }
    if( $status != 200 ) {
        $response = couponVerifyResponse($status);
    }
    $response['status'] = $status;
    return $response;
}

function currencyArray($code = null)
{
    $currency = [
        [ 'code' => 'AED', 'name' => 'United Arab Emirates dirham', 'symbol' => 'د.إ'],
        [ 'code' => 'AFN', 'name' => 'Afghan afghani', 'symbol' => '؋'],
        [ 'code' => 'ALL', 'name' => 'Albanian lek', 'symbol' => 'L'],
        [ 'code' => 'AMD', 'name' => 'Armenian dram', 'symbol' => 'AMD'],
        [ 'code' => 'ANG', 'name' => 'Netherlands Antillean guilder', 'symbol' => 'ƒ'],
        [ 'code' => 'AOA', 'name' => 'Angolan kwanza', 'symbol' => 'Kz'],
        [ 'code' => 'ARS', 'name' => 'Argentine peso', 'symbol' => '$'],
        [ 'code' => 'AUD', 'name' => 'Australian dollar', 'symbol' => '$'],
        [ 'code' => 'AWG', 'name' => 'Aruban florin', 'symbol' => 'Afl.'],
        [ 'code' => 'AZN', 'name' => 'Azerbaijani manat', 'symbol' => 'AZN'],
        [ 'code' => 'BAM', 'name' => 'Bosnia and Herzegovina convertible mark', 'symbol' => 'KM'],
        [ 'code' => 'BBD', 'name' => 'Barbadian dollar', 'symbol' => '$'],
        [ 'code' => 'BDT', 'name' => 'Bangladeshi taka', 'symbol' => '৳ '],
        [ 'code' => 'BGN', 'name' => 'Bulgarian lev', 'symbol' => 'лв.'],
        [ 'code' => 'BHD', 'name' => 'Bahraini dinar', 'symbol' => '.د.ب'],
        [ 'code' => 'BIF', 'name' => 'Burundian franc', 'symbol' => 'Fr'],
        [ 'code' => 'BMD', 'name' => 'Bermudian dollar', 'symbol' => '$'],
        [ 'code' => 'BND', 'name' => 'Brunei dollar', 'symbol' => '$'],
        [ 'code' => 'BOB', 'name' => 'Bolivian boliviano', 'symbol' => 'Bs.'],
        [ 'code' => 'BRL', 'name' => 'Brazilian real', 'symbol' => 'R$'],
        [ 'code' => 'BSD', 'name' => 'Bahamian dollar', 'symbol' => '$'],
        [ 'code' => 'BTC', 'name' => 'Bitcoin', 'symbol' => '฿'],
        [ 'code' => 'BTN', 'name' => 'Bhutanese ngultrum', 'symbol' => 'Nu.'],
        [ 'code' => 'BWP', 'name' => 'Botswana pula', 'symbol' => 'P'],
        [ 'code' => 'BYR', 'name' => 'Belarusian ruble (old)', 'symbol' => 'Br'],
        [ 'code' => 'BYN', 'name' => 'Belarusian ruble', 'symbol' => 'Br'],
        [ 'code' => 'BZD', 'name' => 'Belize dollar', 'symbol' => '$'],
        [ 'code' => 'CAD', 'name' => 'Canadian dollar', 'symbol' => '$'],
        [ 'code' => 'CDF', 'name' => 'Congolese franc', 'symbol' => 'Fr'],
        [ 'code' => 'CHF', 'name' => 'Swiss franc', 'symbol' => 'CHF'],
        [ 'code' => 'CLP', 'name' => 'Chilean peso', 'symbol' => '$'],
        [ 'code' => 'CNY', 'name' => 'Chinese yuan', 'symbol' => '¥'],
        [ 'code' => 'COP', 'name' => 'Colombian peso', 'symbol' => '$'],
        [ 'code' => 'CRC', 'name' => 'Costa Rican colón', 'symbol' => '₡'],
        [ 'code' => 'CUC', 'name' => 'Cuban convertible peso', 'symbol' => '$'],
        [ 'code' => 'CUP', 'name' => 'Cuban peso', 'symbol' => '$'],
        [ 'code' => 'CVE', 'name' => 'Cape Verdean escudo', 'symbol' => '$'],
        [ 'code' => 'CZK', 'name' => 'Czech koruna', 'symbol' => 'Kč'],
        [ 'code' => 'DJF', 'name' => 'Djiboutian franc', 'symbol' => 'Fr'],
        [ 'code' => 'DKK', 'name' => 'Danish krone', 'symbol' => 'kr.'],
        [ 'code' => 'DOP', 'name' => 'Dominican peso', 'symbol' => 'RD$'],
        [ 'code' => 'DZD', 'name' => 'Algerian dinar', 'symbol' => 'د.ج'],
        [ 'code' => 'EGP', 'name' => 'Egyptian pound', 'symbol' => 'EGP'],
        [ 'code' => 'ERN', 'name' => 'Eritrean nakfa', 'symbol' => 'Nfk'],
        [ 'code' => 'ETB', 'name' => 'Ethiopian birr', 'symbol' => 'Br'],
        [ 'code' => 'EUR', 'name' => 'Euro', 'symbol' => '€'],
        [ 'code' => 'FJD', 'name' => 'Fijian dollar', 'symbol' => '$'],
        [ 'code' => 'FKP', 'name' => 'Falkland Islands pound', 'symbol' => '£'],
        [ 'code' => 'GBP', 'name' => 'Pound sterling', 'symbol' => '£'],
        [ 'code' => 'GEL', 'name' => 'Georgian lari', 'symbol' => 'ლ'],
        [ 'code' => 'GGP', 'name' => 'Guernsey pound', 'symbol' => '£'],
        [ 'code' => 'GHS', 'name' => 'Ghana cedi', 'symbol' => '₵'],
        [ 'code' => 'GIP', 'name' => 'Gibraltar pound', 'symbol' => '£'],
        [ 'code' => 'GMD', 'name' => 'Gambian dalasi', 'symbol' => 'D'],
        [ 'code' => 'GNF', 'name' => 'Guinean franc', 'symbol' => 'Fr'],
        [ 'code' => 'GTQ', 'name' => 'Guatemalan quetzal', 'symbol' => 'Q'],
        [ 'code' => 'GYD', 'name' => 'Guyanese dollar', 'symbol' => '$'],
        [ 'code' => 'HKD', 'name' => 'Hong Kong dollar', 'symbol' => '$'],
        [ 'code' => 'HNL', 'name' => 'Honduran lempira', 'symbol' => 'L'],
        [ 'code' => 'HRK', 'name' => 'Croatian kuna', 'symbol' => 'kn'],
        [ 'code' => 'HTG', 'name' => 'Haitian gourde', 'symbol' => 'G'],
        [ 'code' => 'HUF', 'name' => 'Hungarian forint', 'symbol' => 'Ft'],
        [ 'code' => 'IDR', 'name' => 'Indonesian rupiah', 'symbol' => 'Rp'],
        [ 'code' => 'ILS', 'name' => 'Israeli new shekel', 'symbol' => '₪'],
        [ 'code' => 'IMP', 'name' => 'Manx pound', 'symbol' => '£'],
        [ 'code' => 'INR', 'name' => 'Indian rupee', 'symbol' => '₹'],
        [ 'code' => 'IQD', 'name' => 'Iraqi dinar', 'symbol' => 'د.ع'],
        [ 'code' => 'IRR', 'name' => 'Iranian rial', 'symbol' => '﷼'],
        [ 'code' => 'IRT', 'name' => 'Iranian toman', 'symbol' => 'تومان'],
        [ 'code' => 'ISK', 'name' => 'Icelandic króna', 'symbol' => 'kr.'],
        [ 'code' => 'JEP', 'name' => 'Jersey pound', 'symbol' => '£'],
        [ 'code' => 'JMD', 'name' => 'Jamaican dollar', 'symbol' => '$'],
        [ 'code' => 'JOD', 'name' => 'Jordanian dinar', 'symbol' => 'د.ا'],
        [ 'code' => 'JPY', 'name' => 'Japanese yen', 'symbol' => '¥'],
        [ 'code' => 'KES', 'name' => 'Kenyan shilling', 'symbol' => 'KSh'],
        [ 'code' => 'KGS', 'name' => 'Kyrgyzstani som', 'symbol' => 'сом'],
        [ 'code' => 'KHR', 'name' => 'Cambodian riel', 'symbol' => '៛'],
        [ 'code' => 'KMF', 'name' => 'Comorian franc', 'symbol' => 'Fr'],
        [ 'code' => 'KPW', 'name' => 'North Korean won', 'symbol' => '₩'],
        [ 'code' => 'KRW', 'name' => 'South Korean won', 'symbol' => '₩'],
        [ 'code' => 'KWD', 'name' => 'Kuwaiti dinar', 'symbol' => 'د.ك'],
        [ 'code' => 'KYD', 'name' => 'Cayman Islands dollar', 'symbol' => '$'],
        [ 'code' => 'KZT', 'name' => 'Kazakhstani tenge', 'symbol' => '₸'],
        [ 'code' => 'LAK', 'name' => 'Lao kip', 'symbol' => '₭'],
        [ 'code' => 'LBP', 'name' => 'Lebanese pound', 'symbol' => 'ل.ل'],
        [ 'code' => 'LKR', 'name' => 'Sri Lankan rupee', 'symbol' => 'රු'],
        [ 'code' => 'LRD', 'name' => 'Liberian dollar', 'symbol' => '$'],
        [ 'code' => 'LSL', 'name' => 'Lesotho loti', 'symbol' => 'L'],
        [ 'code' => 'LYD', 'name' => 'Libyan dinar', 'symbol' => 'ل.د'],
        [ 'code' => 'MAD', 'name' => 'Moroccan dirham', 'symbol' => 'د.م.'],
        [ 'code' => 'MDL', 'name' => 'Moldovan leu', 'symbol' => 'MDL'],
        [ 'code' => 'MGA', 'name' => 'Malagasy ariary', 'symbol' => 'Ar'],
        [ 'code' => 'MKD', 'name' => 'Macedonian denar', 'symbol' => 'ден'],
        [ 'code' => 'MMK', 'name' => 'Burmese kyat', 'symbol' => 'Ks'],
        [ 'code' => 'MNT', 'name' => 'Mongolian tögrög', 'symbol' => '₮'],
        [ 'code' => 'MOP', 'name' => 'Macanese pataca', 'symbol' => 'P'],
        [ 'code' => 'MRU', 'name' => 'Mauritanian ouguiya', 'symbol' => 'UM'],
        [ 'code' => 'MUR', 'name' => 'Mauritian rupee', 'symbol' => '₨'],
        [ 'code' => 'MVR', 'name' => 'Maldivian rufiyaa', 'symbol' => '.ރ'],
        [ 'code' => 'MWK', 'name' => 'Malawian kwacha', 'symbol' => 'MK'],
        [ 'code' => 'MXN', 'name' => 'Mexican peso', 'symbol' => '$'],
        [ 'code' => 'MYR', 'name' => 'Malaysian ringgit', 'symbol' => 'RM'],
        [ 'code' => 'MZN', 'name' => 'Mozambican metical', 'symbol' => 'MT'],
        [ 'code' => 'NAD', 'name' => 'Namibian dollar', 'symbol' => 'N$'],
        [ 'code' => 'NGN', 'name' => 'Nigerian naira', 'symbol' => '₦'],
        [ 'code' => 'NIO', 'name' => 'Nicaraguan córdoba', 'symbol' => 'C$'],
        [ 'code' => 'NOK', 'name' => 'Norwegian krone', 'symbol' => 'kr'],
        [ 'code' => 'NPR', 'name' => 'Nepalese rupee', 'symbol' => '₨'],
        [ 'code' => 'NZD', 'name' => 'New Zealand dollar', 'symbol' => '$'],
        [ 'code' => 'OMR', 'name' => 'Omani rial', 'symbol' => 'ر.ع.'],
        [ 'code' => 'PAB', 'name' => 'Panamanian balboa', 'symbol' => 'B/.'],
        [ 'code' => 'PEN', 'name' => 'Sol', 'symbol' => 'S/'],
        [ 'code' => 'PGK', 'name' => 'Papua New Guinean kina', 'symbol' => 'K'],
        [ 'code' => 'PHP', 'name' => 'Philippine peso', 'symbol' => '₱'],
        [ 'code' => 'PKR', 'name' => 'Pakistani rupee', 'symbol' => '₨'],
        [ 'code' => 'PLN', 'name' => 'Polish złoty', 'symbol' => 'zł'],
        [ 'code' => 'PRB', 'name' => 'Transnistrian ruble', 'symbol' => 'р.'],
        [ 'code' => 'PYG', 'name' => 'Paraguayan guaraní', 'symbol' => '₲'],
        [ 'code' => 'QAR', 'name' => 'Qatari riyal', 'symbol' => 'ر.ق'],
        [ 'code' => 'RON', 'name' => 'Romanian leu', 'symbol' => 'lei'],
        [ 'code' => 'RSD', 'name' => 'Serbian dinar', 'symbol' => 'рсд'],
        [ 'code' => 'RUB', 'name' => 'Russian ruble', 'symbol' => '₽'],
        [ 'code' => 'RWF', 'name' => 'Rwandan franc', 'symbol' => 'Fr'],
        [ 'code' => 'SAR', 'name' => 'Saudi riyal', 'symbol' => 'ر.س'],
        [ 'code' => 'SBD', 'name' => 'Solomon Islands dollar', 'symbol' => '$'],
        [ 'code' => 'SCR', 'name' => 'Seychellois rupee', 'symbol' => '₨'],
        [ 'code' => 'SDG', 'name' => 'Sudanese pound', 'symbol' => 'ج.س.'],
        [ 'code' => 'SEK', 'name' => 'Swedish krona', 'symbol' => 'kr'],
        [ 'code' => 'SGD', 'name' => 'Singapore dollar', 'symbol' => '$'],
        [ 'code' => 'SHP', 'name' => 'Saint Helena pound', 'symbol' => '£'],
        [ 'code' => 'SLL', 'name' => 'Sierra Leonean leone', 'symbol' => 'Le'],
        [ 'code' => 'SOS', 'name' => 'Somali shilling', 'symbol' => 'Sh'],
        [ 'code' => 'SRD', 'name' => 'Surinamese dollar', 'symbol' => '$'],
        [ 'code' => 'SSP', 'name' => 'South Sudanese pound', 'symbol' => '£'],
        [ 'code' => 'STN', 'name' => 'São Tomé and Príncipe dobra', 'symbol' => 'Db'],
        [ 'code' => 'SYP', 'name' => 'Syrian pound', 'symbol' => 'ل.س'],
        [ 'code' => 'SZL', 'name' => 'Swazi lilangeni', 'symbol' => 'E'],
        [ 'code' => 'THB', 'name' => 'Thai baht', 'symbol' => '฿'],
        [ 'code' => 'TJS', 'name' => 'Tajikistani somoni', 'symbol' => 'ЅМ'],
        [ 'code' => 'TMT', 'name' => 'Turkmenistan manat', 'symbol' => 'm'],
        [ 'code' => 'TND', 'name' => 'Tunisian dinar', 'symbol' => 'د.ت'],
        [ 'code' => 'TOP', 'name' => 'Tongan paʻanga', 'symbol' => 'T$'],
        [ 'code' => 'TRY', 'name' => 'Turkish lira', 'symbol' => '₺'],
        [ 'code' => 'TTD', 'name' => 'Trinidad and Tobago dollar', 'symbol' => '$'],
        [ 'code' => 'TWD', 'name' => 'New Taiwan dollar', 'symbol' => 'NT$'],
        [ 'code' => 'TZS', 'name' => 'Tanzanian shilling', 'symbol' => 'Sh'],
        [ 'code' => 'UAH', 'name' => 'Ukrainian hryvnia', 'symbol' => '₴'],
        [ 'code' => 'UGX', 'name' => 'Ugandan shilling', 'symbol' => 'UGX'],
        [ 'code' => 'USD', 'name' => 'United States (US) dollar', 'symbol' => '$'],
        [ 'code' => 'UYU', 'name' => 'Uruguayan peso', 'symbol' => '$'],
        [ 'code' => 'UZS', 'name' => 'Uzbekistani som', 'symbol' => 'UZS'],
        [ 'code' => 'VEF', 'name' => 'Venezuelan bolívar', 'symbol' => 'Bs F'],
        [ 'code' => 'VES', 'name' => 'Bolívar soberano', 'symbol' => 'Bs.S'],
        [ 'code' => 'VND', 'name' => 'Vietnamese đồng', 'symbol' => '₫'],
        [ 'code' => 'VUV', 'name' => 'Vanuatu vatu', 'symbol' => 'Vt'],
        [ 'code' => 'WST', 'name' => 'Samoan tālā', 'symbol' => 'T'],
        [ 'code' => 'XAF', 'name' => 'Central African CFA franc', 'symbol' => 'CFA'],
        [ 'code' => 'XCD', 'name' => 'East Caribbean dollar', 'symbol' => '$'],
        [ 'code' => 'XOF', 'name' => 'West African CFA franc', 'symbol' => 'CFA'],
        [ 'code' => 'XPF', 'name' => 'CFP franc', 'symbol' => 'Fr'],
        [ 'code' => 'YER', 'name' => 'Yemeni rial', 'symbol' => '﷼'],
        [ 'code' => 'ZAR', 'name' => 'South African rand', 'symbol' => 'R'],
        [ 'code' => 'ZMW', 'name' => 'Zambian kwacha', 'symbol' => 'ZK'],
    ];

    if($code != null)
    {
        $currency = collect($currency)->where('code', $code)->first();
    }
    return $currency;
}

function driver_common_document($driver) {
    $documents = Document::where('is_required',1)->where('status', 1)->pluck('id')->toArray();
    $is_common_document = $driver->driverDocument()->whereIn('document_id', $documents)->count();

    if(count($documents) == $is_common_document) {
        return true;
    } else {
        return false;
    }
}

function driver_required_document($driver) {
    $required_document = $driver->driverDocument()->pluck('document_id')->toArray();
    $documents = Document::where('is_required',1)->where('status', 1)->whereNotIn('id',$required_document)->get();

    return $documents;
}

function stringLong($str = '', $type = 'title', $length = 0) //Add … if string is too long
{
    if ($length != 0) {
        return strlen($str) > $length ? mb_substr($str, 0, $length) . "..." : $str;
    }
    if ($type == 'desc') {
        return strlen($str) > 150 ? mb_substr($str, 0, 150) . "..." : $str;
    } elseif ($type == 'title') {
        return strlen($str) > 15 ? mb_substr($str, 0, 25) . "..." : $str;
    } else {
        return $str;
    }
}

function og_get_distance_matrix_multiple_destination($pick_lat, $pick_lng, $drop_lat, $drop_lng, $drop_latlng, $traffic = false)
{
    $distance = 0;
    $duration = 0;
    for ($i = 0; $i <= count($drop_latlng); $i++)
    {
        if( $i == 0 ) {
            $response = og_get_distance_matrix($pick_lat, $pick_lng, $drop_latlng[$i]['latitude'], $drop_latlng[$i]['longitude']);
            $distance += distance_value_from_distance_matrix($response);
            $duration += duration_value_from_distance_matrix($response);
        } elseif( count($drop_latlng) == $i ) {
            $response = og_get_distance_matrix($drop_latlng[$i-1]['latitude'], $drop_latlng[$i-1]['longitude'], $drop_lat, $drop_lng);
            $distance += distance_value_from_distance_matrix($response);
            $duration += duration_value_from_distance_matrix($response);
        }else {
            $response = og_get_distance_matrix($drop_latlng[$i-1]['latitude'], $drop_latlng[$i-1]['longitude'], $drop_latlng[$i]['latitude'], $drop_latlng[$i]['longitude']);
            $distance += distance_value_from_distance_matrix($response);
            $duration += duration_value_from_distance_matrix($response);
        }
    }

    return [
        'duration' => $duration,
        'distance' => $distance,
    ];
}

function maskSensitiveInfo($type, $info)
{
    if ($type === 'email' && empty($info) or $type === 'contact_number' && empty($info)) {
        return '-';
    }
    if(env('APP_DEMO')) {
        switch ($type) {
            case 'email':
                $parts = explode('@', $info);
                $username = $parts[0];
                $domain = $parts[1];
                $maskedUsername = substr($username, 0, 1) . str_repeat('*', strlen($username) - 1);
                return $maskedUsername . '@' . $domain;

            case 'contact_number':
                return substr($info, 0, 3) . str_repeat('*', strlen($info) - 4) . substr($info, -2);

            default:
                return $info;
        }
    } else {
        return $info;
    }
}

if (!function_exists('getDaysOfWeek')) {
    function getDaysOfWeek()
    {
        return [
            '1' => 'Monday',
            '2' => 'Tuesday',
            '3' => 'Wednesday',
            '4' => 'Thursday',
            '5' => 'Friday',
            '6' => 'Saturday',
            '7' => 'Sunday',
        ];
    }
}

if (!function_exists('getSurgePrice')) {
    function getSurgePrice($ride_datetime, $region_id, $pickup_lat = null, $pickup_lng = null, $drop_lat = null, $drop_lng = null) {
        if ($ride_datetime === null || $region_id === null  || $pickup_lat === null || $pickup_lng === null || $drop_lat === null || $drop_lng === null) {
            return null;
        }
        $surge_price_setting_value = SettingData('ride', 'surge_price') ?? null;
        if ($surge_price_setting_value == 1) {
            $region = Region::select('distance_unit', 'timezone')->find($region_id);
            $distance_unit = $region?->distance_unit ?? 'km'; // default to km if not found
            $timezone = $region?->timezone ?? 'UTC';

            $ride_datetime = \Carbon\Carbon::parse($ride_datetime, $timezone);          
            $day_id = $ride_datetime->format('N');            
            $current_time = $ride_datetime->format('H:i');           

            $surge_prices = SurgePrice::where('region_id',$region_id)->whereJsonContains('day', $day_id)->get();

            if ($surge_prices) {
                foreach ($surge_prices as $surge_price) {
                    $from_times = $surge_price->from_time;               
                    $to_times = $surge_price->to_time;

                    foreach ($from_times as $index => $from_time) {
                        $to_time = $to_times[$index];
                
                        // Check radius match
                        // Use Carbon for reliable time-only comparison (avoids strtotime date sensitivity)
                        $rideCarbon = \Carbon\Carbon::createFromFormat('H:i', $current_time);
                        $fromCarbon = \Carbon\Carbon::createFromFormat('H:i', $from_time);
                        $toCarbon = \Carbon\Carbon::createFromFormat('H:i', $to_time);
                        if ($rideCarbon->between($fromCarbon, $toCarbon)) {
                            // Now check lat/lng/radius                            
                            if($surge_price->latitude && $surge_price->longitude && $surge_price->radius){ 
                                
                                
                                $pickup_distance = haversineDistance($pickup_lat, $pickup_lng, $surge_price->latitude, $surge_price->longitude);
                                //$drop_distance = haversineDistance($drop_lat, $drop_lng, $surge_price->latitude, $surge_price->longitude);

                                if($distance_unit == 'mile'){
                                    $pickup_distance = km_to_mile($pickup_distance);
                                    //$drop_distance = km_to_mile($drop_distance);
                                }                                
                                if($pickup_distance <= $surge_price->radius) {
                                    return $surge_price;
                                }
                            }else{
                                // No lat/lng/radius defined — treat as global for that region & time
                                return $surge_price;
                            }                        
                        }
                    }
                }
            }
        } 
        $surge_price = 0;
        return $surge_price;
    }
}

function updateLanguageVersion()
{
    $language_version_data = LanguageVersionDetail::find(1);
    return $language_version_data->increment('version_no',1);
}

function og_language_direction($language = null)
{
    if (empty($language)) {
        $language = app()->getLocale();
    }
    $language = strtolower(substr($language, 0, 2));
    $rtlLanguages = [
        'ar', //  'العربية', Arabic
        'arc', //  'ܐܪܡܝܐ', Aramaic
        'bcc', //  'بلوچی مکرانی', Southern Balochi`
        'bqi', //  'بختياري', Bakthiari
        'ckb', //  'Soranî / کوردی', Sorani Kurdish
        'dv', //  'ދިވެހިބަސް', Dhivehi
        'fa', //  'فارسی', Persian
        'glk', //  'گیلکی', Gilaki
        'he', //  'עברית', Hebrew
        'lrc', //- 'لوری', Northern Luri
        'mzn', //  'مازِرونی', Mazanderani
        'pnb', //  'پنجابی', Western Punjabi
        'ps', //  'پښتو', Pashto
        'sd', //  'سنڌي', Sindhi
        'ug', //  'Uyghurche / ئۇيغۇرچە', Uyghur
        'ur', //  'اردو', Urdu
        'yi', //  'ייִדיש', Yiddish
    ];
    if (in_array($language, $rtlLanguages)) {
        return 'rtl';
    }

    return 'ltr';
}

function rideStatus() {
    return [
        'new_ride_requested' => __('message.new_ride_requested'),
        'pending' => __('message.pending'),
        'accepted' => __('message.accepted'),
        'arriving' => __('message.arriving'),
        'arrived' => __('message.arrived'),
        'in_progress' => __('message.in_progress'),
        'completed' => __('message.completed'),
        'canceled' => __('message.canceled'),
    ];
}


function generateUniqueReferralCode()
{
    $attempts = 0;
    $maxAttempts = 10;
    $length = 15;
    
    do {
        // Increase length if too many collisions
        if ($attempts >= $maxAttempts) {
            $length++;
            $attempts = 0;
        }
        
        $code = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, $length));
        $attempts++;
        
    } while (User::where('referral_code', $code)->exists() && $length <= 19);

    return $code;
}

function validateReferralCode($code)
{
    return User::where('referral_code', $code)->first();
}

function validateDeviceId($deviceId)
{
    return !User::where('device_id', $deviceId)->exists();
}

function createReferral($referrerId, $referredId, $validReferral = true)
{
    $referral_max_limit = SettingData('referral', 'referral_max_limit') ?? null;
    
    $status = 'pending';
    $comment = null;

    if (!$validReferral) {
        $status = 'failed';
        $comment = 'Device already registered';
    } elseif ($referral_max_limit > 0) {
        $referral_count = Referral::where('referrer_id', $referrerId)->count();
            
        if ($referral_count >= $referral_max_limit) {
            $status = 'failed';
            $comment = 'Referral limit exceeded';
        }
    }
    
    return Referral::create([
        'referrer_id' => $referrerId,
        'referred_id' => $referredId,        
        'status' => $status,
        'comment' => $comment
    ]);
}

function processReferral($referrerId, $referredId)
{
    try {
        DB::beginTransaction();

        $referral = Referral::where([
            'referrer_id' => $referrerId,
            'referred_id' => $referredId,
            'status' => 'pending',
        ])->first();

        if ($referral) {
            $referral->update(['status' => 'complete']);            
            
            $bonusPoints = SettingData('referral', 'referral_bonus_points') ?? 0;
            $point_data = Point::firstOrCreate(['user_id' => $referrerId]);
            
            $total_points = $point_data->total_points + $bonusPoints;
            $point_data->total_points = $total_points;
            $point_data->save();

            // Create point history record
            $point_history = [
                'user_id' => $referrerId,
                'ride_request_id' => null,
                'type' => 'credit',
                'transaction_type' => 'referral',
                'amount' => $bonusPoints,
                'balance' => $total_points,
                'datetime' => date('Y-m-d H:i:s')
            ];
            PointHistory::create($point_history);
        }
        DB::commit();
        return true;
    } catch(\Exception $e) {
        DB::rollback();
        return json_custom_response($e);
    }
}

function getAddressFromLatLong($lat,$lng){
    $google_map_api_key = env('GOOGLE_MAP_KEY');
    $response = Http::get("https://maps.googleapis.com/maps/api/geocode/json", [
        'latlng' => "$lat,$lng",
        'key' => $google_map_api_key
    ]);

    $data = $response->json();

    if ($response->successful() && isset($data['results'][0])) {
        $address = preg_replace('/^\b[\w\d]+\+\w+\b,?\s*/', '',$data['results'][0]['formatted_address']);
        return trim($address);
    }
    return "Address not found";
}

function convertSecondsToReadableTime($totalSeconds)
{
    $hours = floor($totalSeconds / 3600);
    $minutes = floor(($totalSeconds % 3600) / 60);

    $parts = [];

    if ($hours > 0) {
        $parts[] = $hours . ' hr' . ($hours > 1 ? 's' : '');
    }

    if ($minutes > 0) {
        $parts[] = $minutes . ' min' . ($minutes > 1 ? 's' : '');
    }

    return implode(' ', $parts);
}

function getActiveAdminsRoles() {
    return Role::where('status', 1)->whereNotIn('name', ['driver', 'rider'])->pluck('name')->toArray();
}

if (!function_exists('createStripeCustomer')) {
    function createStripeCustomer($email, $name, $phone, $type)
    {
        $stripeSecretKey = getStripeSecretKey();

        $response = Http::withToken($stripeSecretKey)->asForm()->post('https://api.stripe.com/v1/customers', [
            'email' => $email,
            'name' => $name,
            'phone' => $phone,
        ]);

        if ($response->failed()) {
            return ['error' => $response->json()['error']['message'] ?? 'Unknown error'];
        }

        return $response->json();
    }
}

if (!function_exists('updateStripeCustomer')) {
    function updateStripeCustomer($customerId, $email, $name, $phone)
    {
        $stripeSecretKey = getStripeSecretKey();
        $response = Http::withToken($stripeSecretKey)->asForm()->post("https://api.stripe.com/v1/customers/$customerId", [
            'email' => $email,
            'name' => $name,
            'phone' => $phone,
        ]);

        if ($response->failed()) {
            return ['error' => $response->json()['error']['message'] ?? 'Unknown error'];
        }

        return $response->json();
    }
}

if (!function_exists('deleteStripeCustomer')) {
    function deleteStripeCustomer($customerId)
    {
        $stripeSecretKey = getStripeSecretKey();
        $response = Http::withToken($stripeSecretKey)->delete("https://api.stripe.com/v1/customers/$customerId");

        if ($response->failed()) {
            return ['error' => $response->json()['error']['message'] ?? 'Unknown error'];
        }

        return $response->json();
    }
}

if (!function_exists('hasStripeKeys')) {
    /**
     * Check whether Stripe is configured in the DB (active gateway with a secret key).
     * Use this as a gate before any Stripe API call.
     */
    function hasStripeKeys()
    {
        $paymentGateway = PaymentGateway::where('type', 'stripe')->where('status', 1)->first();
        if (!$paymentGateway) {
            return false;
        }
        $values = $paymentGateway['is_test'] == 1
            ? ($paymentGateway['test_value'] ?? [])
            : ($paymentGateway['live_value'] ?? []);
        return !empty($values['secret_key']);
    }
}

if (!function_exists('getStripeSecretKey')) {
    function getStripeSecretKey()
    {
        $paymentGateway = PaymentGateway::where('type', 'stripe')->where('status', 1)->first();
        if (!$paymentGateway) {
            return null;
        }
        return $paymentGateway['is_test'] == 1
            ? ($paymentGateway['test_value']['secret_key'] ?? null)
            : ($paymentGateway['live_value']['secret_key'] ?? null);
    }
}


if (!function_exists('cancelStripePayment')) {
    function cancelStripePayment($payment_intent_id)
    {
        $stripeSecretKey = getStripeSecretKey();
        $response = Http::withToken($stripeSecretKey)->asForm()
            ->post("https://api.stripe.com/v1/payment_intents/{$payment_intent_id}/cancel");

        if ($response->successful()) {
            $result = $response->json();
            return [
                'success' => ($result['status'] ?? '') === 'canceled',
                'data'    => $result,
            ];
        }
    
        return [
            'success' => false,
            'error'   => $response->json(),
        ];
    }
}

if (!function_exists('createStripePaymentIntent')) {
    function createStripePaymentIntent($customer_id, $amount, $currency){
        $stripeSecretKey = getStripeSecretKey();

        $customerResponse = Http::withToken($stripeSecretKey)
        ->get("https://api.stripe.com/v1/customers/{$customer_id}");

        if (!$customerResponse->successful()) {
            throw new \Exception('Unable to retrieve customer.');
        }

        $customer = $customerResponse->json();
        $defaultPaymentMethod = $customer['invoice_settings']['default_payment_method'] ?? null;

        if (!$defaultPaymentMethod) {
            throw new \Exception('No default payment method set.');
        }

        $paymentIntent = Http::withToken($stripeSecretKey)->asForm()->post(
            'https://api.stripe.com/v1/payment_intents',
            [
                'amount'               => (int) $amount,
                'currency'             => $currency,
                'customer'             => $customer_id,
                'payment_method'       => $defaultPaymentMethod,
                'payment_method_types' => ['card'],
                'confirmation_method'  => 'automatic',
                'confirm'              => true,
                'capture_method'       => 'manual',
            ]
        );

        if (!$paymentIntent->successful()) {
            throw new \Exception('Unable to create payment intent: ' . json_encode($paymentIntent->json()));
        }

        return $paymentIntent->json();
    }
}

if (!function_exists('captureStripePaymentIntent')) {
    function captureStripePaymentIntent($payment_intent_id, $amount){
        $stripeSecretKey = getStripeSecretKey();

        $payload = [];
        if ($amount) {
            $payload['amount_to_capture'] = (int) $amount;
        }

        $response = Http::withToken($stripeSecretKey)->asForm()->post("https://api.stripe.com/v1/payment_intents/{$payment_intent_id}/capture", $payload);

        if ($response->successful()) {
            $result = $response->json();
            return [
                'success' => ($result['status'] ?? '') === 'succeeded',
                'data'    => $result,
            ];
        }
    
        return [
            'success' => false,
            'error'   => $response->json(),
        ];
    }
}

if (!function_exists('debitRiderWallet')) {
    function debitRiderWallet($rider_id, $amount, $currency, $ride_id)
    {
        $wallet = Wallet::firstOrCreate(['user_id' => $rider_id]);
        $wallet->decrement('total_amount', $amount);

        WalletHistory::create([
            'user_id'          => $rider_id,
            'type'             => 'debit',
            'transaction_type' => 'tips',
            'currency'         => $currency,
            'amount'           => $amount,
            'balance'          => $wallet->total_amount,
            'ride_request_id'  => $ride_id,
            'datetime'         => now(),
        ]);
    }
}

if (!function_exists('creditDriverWallet')) {
    function creditDriverWallet($driver_id, $amount, $currency, $ride_id)
    {
        $wallet = Wallet::firstOrCreate(['user_id' => $driver_id]);
        $wallet->increment('total_amount', $amount);

        WalletHistory::create([
            'user_id'          => $driver_id,
            'type'             => 'credit',
            'transaction_type' => 'tips',
            'currency'         => $currency,
            'amount'           => $amount,
            'balance'          => $wallet->total_amount,
            'ride_request_id'  => $ride_id,
            'datetime'         => now(),
        ]);
    }
}

if (!function_exists('recordRideTip')) {
    function recordRideTip($ride_id, $amount, $payment_type, $status, $received_by, $payment_intent_id = null)
    {
        RideTip::create([
            'ride_request_id'   => $ride_id,
            'tip_amount'        => $amount,
            'payment_type'      => $payment_type,
            'status'            => $status,
            'received_by'       => $received_by,
            'payment_intent_id' => $payment_intent_id,
        ]);
    }
}

function formatPhoneNumber($number)
{
    $formats = [
        '+1' => '+1 XXX XXX XXXX',   // US, Canada
        '+61' => '+61 XXX XXX XXX',   // Australia
        '+44' => '+44 XXXX XXXXXX',   // UK
        '+64' => '+64 XXX XXX XXXX',  // New Zealand
        '+91' => '+91 XXXXX XXXXX',   // India
    ];

    foreach ($formats as $code => $format) {
        if (str_starts_with($number, $code)) {

            $digits = preg_replace('/\D/', '', $number);
            $digits = substr($digits, strlen($code) - 1);

            $index = 0;
            $formatted = $code . ' ';

            foreach (str_split(substr($format, strlen($code) + 1)) as $char) {
                if ($char === 'X') {
                    $formatted .= $digits[$index] ?? '';
                    $index++;
                } else {
                    $formatted .= $char;
                }
            }

            return $formatted;
        }
    }

    return $number;
}

/**
 * Find sibling accounts — users with the same email but a different user_type.
 *
 * @param  \App\Models\User  $user
 * @return \Illuminate\Database\Eloquent\Collection
 */
function getSiblingAccounts(User $user)
{
    if (empty($user->email)) {
        return collect();
    }

    return User::where('email', $user->email)
        ->where('id', '!=', $user->id)
        ->get();
}

/**
 * Sync shared fields (password, avatar, display_name) to sibling accounts.
 *
 * @param  \App\Models\User  $user   The source user whose data should be copied.
 * @param  array             $fields The field names to sync (e.g. ['password', 'display_name']).
 * @return void
 */
function syncSharedFieldsToSiblings(User $user, array $fields): void
{
    $siblings = getSiblingAccounts($user);

    if ($siblings->isEmpty()) {
        return;
    }

    $data = [];
    foreach ($fields as $field) {
        if ($user->{$field} !== null) {
            $data[$field] = $user->{$field};
        }
    }

    if (!empty($data)) {
        User::where('email', $user->email)
            ->where('id', '!=', $user->id)
            ->update($data);
    }
}

/**
 * Clones an existing user's shared attributes to instantly create a new role account.
 * Used for seamless Auto-Cloning during login across different platforms.
 *
 * @param  User $existingUser
 * @param  string           $newRole (e.g. 'rider' or 'driver')
 * @return User
 */
function cloneUserForNewRole(User $existingUser, string $newRole)
{
    $cloneData = $existingUser->only([
        'first_name', 'last_name', 'display_name', 'email', 'contact_number',
        'password', 'country_code', 'gender', 'stripe_customer_id', 'uid', 'status'
    ]);
    
    $cloneData['user_type'] = $newRole;
    $cloneData['username'] = stristr($cloneData['email'] ?? $cloneData['contact_number'], "@", true) . rand(100,1000);
    
    // Generate Stripe Customer if cloning into Rider and one doesn't exist
    if ($newRole === 'rider' && empty($cloneData['stripe_customer_id']) && hasStripeKeys()) {
        $stripeCustomer = createStripeCustomer($cloneData['email'] ?? '', $cloneData['display_name'] ?? '', $cloneData['contact_number'] ?? '', 'stripe');
        if (isset($stripeCustomer['id'])) {
            $cloneData['stripe_customer_id'] = $stripeCustomer['id'];
        }
    }

    $newUser = User::create($cloneData);
    $newUser->assignRole($newRole);

    // Provision a digital wallet for the new account
    $newUser->userWallet()->create(['total_amount' => 0 ]);

    // Try to sync profile image if present
    try {
        $media = $existingUser->getFirstMedia('profile_image');
        if ($media && file_exists($media->getPath())) {
            $newUser->addMedia($media->getPath())->preservingOriginal()->toMediaCollection('profile_image');
        }
    } catch (\Exception $e) {
        \Log::error('Auto-clone media sync failed: ' . $e->getMessage());
    }

    return $newUser;
}
if (!function_exists('getPaystackSecretKey')) {
    function getPaystackSecretKey()
    {
        $paymentGateway = PaymentGateway::where('type', 'paystack')->where('status', 1)->first();
        if (!$paymentGateway) {
            return null;
        }
        return $paymentGateway['is_test'] == 1 ? $paymentGateway['test_value']['secret_key'] : $paymentGateway['live_value']['secret_key'];
    }
}
if (!function_exists('generateUniqueUsername')) {

    function generateUniqueUsername(?string $firstName, ?string $lastName): string 
    {
        // 1. Clean and combine names into lowercase alphanumeric only
        $base = preg_replace('/[^a-z0-9]/', '', strtolower(trim(($firstName ?? '') . ($lastName ?? '')))) ?: 'user';

        // 2. Pad with random numbers if total length is under 6 characters
        if (($len = strlen($base)) < 6) {
            $base .= random_int((int)str_repeat('1', 6 - $len), (int)str_repeat('9', 6 - $len));
        }

        // 3. Fast check: If 'laxmanroriyatesting' is free, take it immediately
        if (!User::where('username', $base)->exists()) {
            return $base;
        }

        // 4. If it exists, fetch all matches starting with 'laxmanroriyatesting'
        // We use flip() to turn values into array keys for blazing fast O(1) lookups
        $existing = User::where('username', 'LIKE', "{$base}%")
            ->pluck('username')
            ->flip(); 

        // 5. Safely loop to find the first open slot (e.g., laxmanroriyatesting1, laxmanroriyatesting2)
        $counter = 1;
        while ($existing->has($base . $counter)) {
            $counter++;
        }

        return $base . $counter;
    }
}