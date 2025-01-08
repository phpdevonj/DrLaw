<?php

namespace App\Http\Controllers\Frontendwebsite;
use App\Http\Controllers\Controller;
use App\Models\FrontendData;
use App\Models\Pages;
use Illuminate\Http\Request;
use App\Models\Setting;

class FrontendController extends Controller
{
    public function index()
    {
        $data['dummy_title']  = DummyData('dummy_title');
        $data['dummy_description'] = DummyData('dummy_description');

        $data['app_info'] = [
            'app_title'   => SettingData('app_info', 'app_title') ?? $data['dummy_title'],
            'image_title' => SettingData('app_info', 'image_title') ?? $data['dummy_title'],
            'backgound_image' => getSingleMediaSettingImage(getSettingFirstData('app_info','background_image'),'background_image'),
        ];

        $data['download_app'] = [
            'title' => SettingData('download_app', 'title') ?? $data['dummy_title'],
            'subtitle' => SettingData('download_app', 'subtitle') ?? $data['dummy_title'],
            'play_store' => [
                'url' => SettingData('download_app', 'play_store') ?? 'javascript:void(0)',
                'target' => SettingData('download_app', 'play_store') ? 'target="_blank"' : ''
            ],
            'app_store' => [
                'url' => SettingData('download_app', 'app_store') ?? 'javascript:void(0)',
                'target' => SettingData('download_app', 'app_store') ? 'target="_blank"' : ''
            ],
            'download_app_image' => getSingleMediaSettingImage(getSettingFirstData('download_app','image'),'image','download_app'),
        ];

        $data['our_mission'] = [
            'title' => SettingData('our_mission','title') ?? $data['dummy_title'],
            'our_mission_image' => getSingleMediaSettingImage(getSettingFirstData('our_mission','image'),'image','our_mission'),
        ];

        $data['why_choose'] = [
            'why_choose_image' => getSingleMediaSettingImage(getSettingFirstData('why_choose','image'),'image','why_choose'),
            'title' =>  SettingData('why_choose', 'title') ?? $data['dummy_title'],
            'subtitle' =>  SettingData('why_choose', 'subtitle') ?? $data['dummy_title'],
        ];  

        $data['client_testimonials'] = [
            'title' => SettingData('client_testimonials','title') ?? $data['dummy_title'],
            'subtitle' => SettingData('client_testimonials','subtitle') ?? $data['dummy_title']
        ];

        $our_mission = FrontendData::where('type', 'our_mission')->get();

        if (count($our_mission) <= 0)
        $our_mission[] = (object) [
            "description" => $data['dummy_description'],
        ];


        $why_choose = FrontendData::where('type', 'why_choose')->get();
    
        if (count($why_choose) <= 0)
            $why_choose[] = (object) [
                'id' => null,
                "title" => $data['dummy_title'],
                "description" => $data['dummy_description'],
            ];

        $items = FrontendData::where('type', 'client_testimonials')->get();
        
        if( count($items) <= 0 ) {
            $items[] = (object) [
                'id' => null,
                "title" => $data['dummy_title'],
                "subtitle" => $data['dummy_title'],
                "description" => $data['dummy_description'],
            ];
        }
        
        return view('frontend-website.index', compact('our_mission','why_choose','items','data'));
    }

    public function termofservice()
    {
        return view('frontend-website.termofservice');
    }

    public function privacypolicy()
    {
        return view('frontend-website.privacy_policy');
    }

    public function websiteSettingForm($type)
    {
        $data = config('constant.'.$type);
        $pageTitle = __('message.'.$type);

        foreach ($data as $key => $val) {
            if( in_array( $key, ['background_image', 'logo_image', 'image'])) {
                $data[$key] = Setting::where('type', $type)->where('key',$key)->first();
            } else {
                $data[$key] = Setting::where('type', $type)->where('key',$key)->pluck('value')->first();
            }
        }
        
        return view('websitesection.form', compact('data', 'pageTitle', 'type'));
    }

    public function websiteSettingUpdate(Request $request, $type)
    {
        $data = $request->all();

        foreach(config('constant.'.$type) as $key => $val){
            $input = [
                'type'  => $type,
                'key'   => $key,
                'value' => $data[$key] ?? null,
            ];
            $result = Setting::updateOrCreate(['key' => $key, 'type' => $type],$input);

            if( in_array($key, ['background_image', 'logo_image', 'image'] ) ) {
                uploadMediaFile($result,$request->$key, $key);
            }
        }

        return redirect()->back()->withSuccess(__('message.save_form', ['form' => __('message.'.$type)]));
    }

    public function page($slug)
    {
        $page = Pages::where('slug', $slug)->firstOrFail();
        return view('frontend-website.pages', compact('page'));
    }

}