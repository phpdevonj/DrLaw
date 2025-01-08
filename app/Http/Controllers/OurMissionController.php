<?php

namespace App\Http\Controllers;

use App\Models\FrontendData;
use App\Models\Setting;
use Illuminate\Http\Request;
use App\Http\Requests\OurMissionRequest;

class OurMissionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $pageTitle =  __('message.our_mission');
        $data = FrontendData::where('type', 'our_mission')->get();

        $our_mission = config('constant.our_mission');
        
        foreach ($our_mission as $key => $val) {
            if( in_array( $key, ['title']) ) {
                $our_mission[$key] = Setting::where('type','our_mission')->where('key',$key)->pluck('value')->first();
            } else {
                $our_mission[$key] = Setting::where('type','our_mission')->where('key',$key)->first();
            }
        }

        return view('our_mission.main', compact('pageTitle', 'our_mission', 'data'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $pageTitle = __('message.add_form_title', ['form' => __('message.our_mission')]);

        return view('our_mission.model', compact('pageTitle'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(OurMissionRequest $request)
    {
        $data = $request->all();
        $data['type'] = 'our_mission';
        $result = FrontendData::create($data);

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
        $pageTitle = __('message.update_form_title', ['form' => __('message.our_mission')]);
        $data = FrontendData::findOrFail($id);

        return view('our_mission.model', compact('data', 'pageTitle', 'id'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(OurMissionRequest $request, $id)
    {
        $our_mission = FrontendData::find($id);
        if ($our_mission == null) {
            $message = __('message.not_found_entry', ['name' => __('message.our_mission')]);
            return json_custom_response(['status' => false, 'message' => $message], 400);
        }
        $data = $request->all();
        $data['type'] = 'our_mission';

        $our_mission->fill($data)->update();
        
        $message = __('message.update_form', ['form' => __('message.our_mission')]);

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
        $our_mission = FrontendData::find($id);
        $status = 'error';
        $message = __('message.not_found_entry', ['name' =>__('message.our_mission')]);

        if( $our_mission != '' ) {
            $status = 'success';
            $message = __('message.delete_form',['form' => __('message.'.$our_mission->type)] );
            $our_mission->delete();
        } 
        
        return redirect()->back()->with($status,$message);
    }
}
