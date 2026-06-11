<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CarModel;
use App\Http\Resources\CarModelResource;

class CarModelController extends Controller
{
    public function getList(Request $request)
    {
        $car_models = CarModel::query();

        $car_models->when(request('name'), function ($q) {
            return $q->where('name', 'LIKE', '%' . request('name') . '%');
        });
        
        $per_page = config('constant.PER_PAGE_LIMIT');
        if( $request->has('per_page') && !empty($request->per_page)){
            if(is_numeric($request->per_page))
            {
                $per_page = $request->per_page;
            }
            if($request->per_page == -1 ){
                $per_page = $car_models->count();
            }
        }

        $car_models = $car_models->orderBy('name','asc')->paginate(200);
        $items = CarModelResource::collection($car_models);

        $response = [
            //'pagination' => json_pagination_response($items),
            'data' => $items,
        ];
        
        return json_custom_response($response);
    }
}
