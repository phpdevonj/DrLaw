<?php

namespace App\Http\Controllers;

use App\Http\Requests\WhyChooseRequest;
use App\Models\FrontendData;
use App\Models\Setting;
use Illuminate\Http\Request;

class WhyChooseController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $pageTitle =  __('message.why_choose');

        $data = FrontendData::where('type', 'why_choose')->get();

        $why_choose = config('constant.why_choose');
        
        foreach ($why_choose as $key => $val) {
            if( in_array( $key, ['title', 'subtitle']) ) {
                $why_choose[$key] = Setting::where('type', 'why_choose')->where('key',$key)->pluck('value')->first();
            } else {
                $why_choose[$key] = Setting::where('type', 'why_choose')->where('key',$key)->first();
            }
        }

        return view('why_choose.main', compact('pageTitle', 'why_choose', 'data'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $pageTitle = __('message.add_form_title', ['form' => __('message.why_choose')]);

        return view('why_choose.model', compact('pageTitle'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(WhyChooseRequest $request)
    {
        $data = $request->all();
        $data['type'] = 'why_choose';

        $result = FrontendData::create($data);

        uploadMediaFile($result, $request->frontend_data_image, 'frontend_data_image');

        $type = $result->type ? __('message.' . $result->type) : __('message.record');

        $message = __('message.save_form', ['form' => $type]);

        return response()->json(['status' => true,  'message' => $message,'event' => 'refresh']);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $pageTitle = __('message.update_form_title', ['form' => __('message.why_choose')]);
        $data = FrontendData::findOrFail($id);

        return view('why_choose.model', compact('data', 'pageTitle', 'id'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(WhyChooseRequest $request, $id)
    {
        $frontenddata = FrontendData::find($id);

        if ($frontenddata == null) {
            $message = __('message.not_found_entry', ['name' => __('message.frontenddata')]);
            return json_custom_response(['status' => false, 'message' => $message], 400);
        }
        $data = $request->all();
        $data['type'] = 'why_choose';

        $frontenddata->fill($data)->update();
        $message = __('message.update_form', ['form' => __('message.frontenddata')]);
        
        uploadMediaFile($frontenddata, $request->frontend_data_image, 'frontend_data_image');

        return response()->json(['status' => true, 'event' => 'refresh', 'message' => $message]);
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $why_choose = FrontendData::find($id);
        $status = 'error';
        $message = __('message.not_found_entry', ['name' =>__('message.why_choose')]);

        if( $why_choose != '' ) {
            $status = 'success';
            $message = __('message.delete_form',['form' => __('message.'.$why_choose->type)] );
            $why_choose->delete();
        } 
        
        return redirect()->back()->with($status,$message);
    }
}
