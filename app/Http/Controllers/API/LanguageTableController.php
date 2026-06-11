<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LanguageVersionDetail;
use App\Http\Resources\LanguageTableResource;
use App\Models\LanguageList;

class LanguageTableController extends Controller
{
    public function getList(Request $request)
    {
        $is_allow_deliveryman = SettingData('allow_deliveryman', 'allow_deliveryman') ? true : false;
        $version_data = LanguageVersionDetail::where('version_no',request('version_no'))->first();

        if (isset($version_data) && !empty($version_data)) {
            return json_custom_response([ 'status' => false, 'data' => []]);
        }

        $language_content = LanguageList::query()->where('status','1')->orderBy('id', 'asc')->get();
        $language_version = LanguageVersionDetail::find(1);
        $items = LanguageTableResource::collection($language_content);

        $response = [
            'status' => true,
            'version_code' => $language_version->version_no,
            'default_language_id' => $language_version->default_language_id,
            'data' => $items,
            'allow_deliveryman' => $is_allow_deliveryman,
        ];

        return json_custom_response($response);
    }
}
