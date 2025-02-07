<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Point;
use App\Models\PointHistory;
use App\Http\Resources\PointHistoryResource;
use App\Models\Wallet;
use App\Models\WalletHistory;
use Illuminate\Support\Facades\DB;

class PointController extends Controller
{
    public function getPointDetail(Request $request){
        $points_data = Point::where('user_id', auth()->user()->id)->first();

        if( $points_data == null ) {
            $message = __('message.not_found_entry',['name' => __('message.points')]);
            return json_message_response($message,400);
        }
        $response = [
            'points_data' => $points_data ?? null,
            'total_points'  => $points_data->total_points,
        ];
        return json_custom_response($response);
    }
    
    public function getList(Request $request){
        $point_history = PointHistory::myPointHistory();

        $point_history->when(request('user_id'), function ($q) {
            return $q->where('user_id', request('user_id'));
        });        
        
        $per_page = config('constant.PER_PAGE_LIMIT');        
        if( $request->has('per_page') && !empty($request->per_page)){
            
            if(is_numeric($request->per_page))
            {
                $per_page = $request->per_page;
            }

            if($request->per_page == -1 ){
                $per_page = $point_history->count();
            }
        }
        
        $point_history = $point_history->orderBy('id','desc')->paginate($per_page);
        $items = PointHistoryResource::collection($point_history);

        $points_data = Point::where('user_id', auth()->user()->id)->first();
        $response = [
            'pagination' => json_pagination_response($items),
            'data' => $items,
            'point_balance' => $points_data
        ];
        
        return json_custom_response($response);
    }

    public function pointWithdraw(Request $request){
        $loyalty_program = SettingData('ride', 'loyalty_program') ?? 0;

        if(!$loyalty_program){
            $message = __('message.loyalty_not_enabled');
            return json_message_response($message,400);          
        }

        $data = $request->all();
        $user_id = request()->user_id ?? auth()->user()->id;
        $requested_points = $data['points'] ?? 0;

        // Fetch user's points and wallet
        $points = Point::firstOrCreate(['user_id'=> $user_id ]);
        $wallet = Wallet::firstOrCreate([ 'user_id' => $user_id ]);

        if($points->total_points < $requested_points){
            $message = __('message.insufficient_points');
            return json_message_response($message,400);            
        }

        if(($data['type'] ?? '') !== 'debit'){
            $message = __('message.invalid_transaction_type');
            return json_message_response($message,400); 
        }       

        $point_conversion_value =  SettingData('ride', 'point_value') ?? 0;
        $converted_amount = $requested_points*$point_conversion_value;

        // Deduct points and credit wallet
        $total_points = $points->total_points - $requested_points;
        $points->total_points = $total_points;

        $total_amount = $wallet->total_amount + $converted_amount;   
        $wallet->total_amount = $total_amount;

        try{
            DB::beginTransaction();
            $points->save();
            $wallet->save();
            $data['user_id'] = $points->user_id;                    
            $data['datetime'] = date('Y-m-d H:i:s');
            if($points){
                $data['balance'] = $total_points;
                $data['amount'] = $requested_points;
                $result = PointHistory::create($data); 
            }
            
            if($wallet){
                $data['type'] = 'credit';
                $data['transaction_type'] = 'points';
                $data['balance'] = $total_amount;
                $data['amount'] = $converted_amount;
                $walletResult = WalletHistory::create($data); 
            }
            DB::commit();
        }catch(\Exception $e){
            DB::rollback();
            return json_custom_response($e);
        }

        $message = __('message.withdraw_points');
        $response = [
            'message' => $message
        ];

        return json_custom_response($response);        
    }
}
