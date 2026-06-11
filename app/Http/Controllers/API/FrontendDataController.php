<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FrontendData;
use App\Http\Resources\FrontendDataResource;

class FrontendDataController extends Controller
{
    public function getList(Request $request)
    {
        $frontend_data = FrontendData::query();

        $frontend_data->when(request('type'), function ($q) {
            return $q->where('type', request('type'));
        });

        $frontend_data->when(request('title'), function ($q) {
            return $q->where('title', 'LIKE', '%' . request('title') . '%');
        });

        $per_page = config('constant.PER_PAGE_LIMIT');
        if( $request->has('per_page') && !empty($request->per_page)) {
            if(is_numeric($request->per_page))
            {
                $per_page = $request->per_page;
            }
            if($request->per_page == -1 ){
                $per_page = $frontend_data->count();
            }
        }

        $frontend_data = $frontend_data->orderBy('id', 'desc')->paginate($per_page);
        $items = FrontendDataResource::collection($frontend_data);

        $response = [
            'pagination' => json_pagination_response($items),
            'data' => $items,
        ];

        return json_custom_response($response);
    }
}
