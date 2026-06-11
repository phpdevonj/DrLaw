<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\SettingResource;
use Illuminate\Http\Request;
use App\Models\Setting;
use App\Models\DriverMessage;
use App\Http\Resources\DriverMessageResource;
use App\Models\User;

class SettingController extends Controller
{
    public function getList(Request $request)
    {
        $setting = Setting::query();

        $per_page = config('constant.PER_PAGE_LIMIT');
        if( $request->has('per_page') && !empty($request->per_page)){
            if(is_numeric($request->per_page))
            {
                $per_page = $request->per_page;
            }
            if($request->per_page == -1 ){
                $per_page = $setting->count();
            }
        }

        $setting = $setting->orderBy('key', 'asc')->paginate($per_page);

        $items = SettingResource::collection($setting);
        $status = 200;

        $response = [
            'status'        => $status,
            'pagination'    => json_pagination_response($items),
            'data'          => $items,
        ];

        return json_custom_response($response);
    }

    public function getDriverMessageList(Request $request)
    {
        $auth_user = auth()->user();
        $user = User::find($auth_user->id);

        $driver_message = DriverMessage::query();

        if ($request->has('language_code')) {
            $driver_message->where('language_code', $request->language_code);
        }else{
            $driver_message->where('language_code', 'en');
        }

        if ($request->has('message_type')) {
            $driver_message->where('message_type', $request->message_type);
        }     
        
        if($user->hasRole('driver')) {   
            $userDetails = $user->userDetail;             
            $car_model = $userDetails->car_model ?? '';
            $car_color = $userDetails->car_color ?? '';

            $driverRideDetail = $user->driverRideRequestDetail()->whereIn('status', ['arriving','arrived','in_progress'])->where('is_driver_rated',false)->first();

            $dropoff_time_in_seconds = 0;
            
            if(!empty($driverRideDetail)){
                if($driverRideDetail['status'] == 'arriving' || $driverRideDetail['status'] == 'arrived' ){
                    $place_details = og_get_distance_matrix($user->latitude, $user->longitude, $driverRideDetail->start_latitude, $driverRideDetail->start_longitude);
                }else{
                    $place_details = og_get_distance_matrix($driverRideDetail->start_latitude, $driverRideDetail->start_longitude, $driverRideDetail->end_latitude, $driverRideDetail->end_longitude);
                }
                $dropoff_time_in_seconds = duration_value_from_distance_matrix($place_details)/60 ?? 0;
                $dropoff_time_in_seconds = number_format( (float) $dropoff_time_in_seconds, 2,'.','');
            }

            $specific_location = getAddressFromLatLong($user->latitude, $user->longitude) ?? '';
        }

        $driver_message = $driver_message->orderBy('id', 'asc')->get();

        $dataGrouped = [];

        foreach ($driver_message as $message) {
            $type = $message->message_type; 
            $key = $message->message_key;
            $value = $message->message_value;

            if ($type === 'pick_up' && $user->hasRole('driver')) {
                switch ($key) {
                    case 'arrival':
                        $value = str_replace(['%%car_color%%', '%%car_model%%'], [$car_color, $car_model], $value);
                        break;
                    case 'eta':
                    case 'delay':
                        $value = str_replace('%%x%%', $dropoff_time_in_seconds, $value);
                        break;
                    case 'location_check':
                        $value = str_replace('%%specific_location%%', $specific_location, $value);
                        break;
                }
            }            

            $dataGrouped[$type][] = [
                $key => $value
            ];
        }

        // Final response
        $response = [
            'status' => 200,
            'data' => [
                'lang' => $request->language_code ?? 'en',
                'messages' => $dataGrouped
            ]
        ];

        return json_custom_response($response);
    }
}
