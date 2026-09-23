<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SurgePrice;
use App\Http\Resources\SurgePriceResource;
use MatanYadaev\EloquentSpatial\Objects\Point;
use App\Models\Region;

class SurgePriceController extends Controller
{
    public function getList(Request $request)
    {
        $surge_price_setting_value = SettingData('ride', 'surge_price') ?? null;


        if ((int) $surge_price_setting_value !== 1) {
            return json_custom_response([
                'data' => [],
            ]);
        }

        $user = auth()->user();
        $surge_price = SurgePrice::query();

        if(!empty($user->latitude) && !empty($user->longitude)){  
            $userLocation = new Point($user->latitude, $user->longitude);

            $region = Region::where('status', 1)->whereContains('coordinates', $userLocation)->first();

            if($region){
                $surge_price = $surge_price->where('region_id',$region->id);
            }
        }
        
        $per_page = config('constant.PER_PAGE_LIMIT');
        if( $request->has('per_page') && !empty($request->per_page)){
            if(is_numeric($request->per_page))
            {
                $per_page = $request->per_page;
            }
            if($request->per_page == -1 ){
                $per_page = $surge_price->count();
            }
        }

        $surge_price = $surge_price->orderBy('id','asc')->paginate(100);
        $items = SurgePriceResource::collection($surge_price);

        $response = [
            //'pagination' => json_pagination_response($items),
            'data' => $items,
        ];
        
        return json_custom_response($response);
    }
}
