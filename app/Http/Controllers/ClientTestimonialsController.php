<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FrontendData;
use App\Models\Setting;
use App\Http\Requests\ClientTestimonialRequest;

class ClientTestimonialsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $pageTitle =__('message.client_testimonials');
        $data = FrontendData::where('type', 'client_testimonials')->get();
        $client_testimonials = config('constant.client_testimonials');
        
        foreach ($client_testimonials as $key => $val) {
            if( in_array( $key, ['title', 'subtitle']) ) {
                $client_testimonials[$key] = Setting::where('type','client_testimonials')->where('key',$key)->pluck('value')->first();
            } else {
                $client_testimonials[$key] = Setting::where('type','client_testimonials')->where('key',$key)->first();
            }
        }

        return view('client_testimonials.main', compact('pageTitle', 'data', 'client_testimonials'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $pageTitle = __('message.add_form_title', ['form' => __('message.client_testimonials')]);

        return view('client_testimonials.model', compact('pageTitle'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(ClientTestimonialRequest $request)
    {
        $data = $request->all();
        $data['type'] = 'client_testimonials';
        $result = FrontendData::create($data);

        uploadMediaFile($result, $request->frontend_data_image, 'frontend_data_image');

        $type = $result->type ? __('message.'.$result->type) : __('message.record');

        
        $message = __('message.save_form', ['form' => $type]);
        return response()->json(['status' => true, 'message'=> $message,'event' => 'refresh']);
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
        $pageTitle = __('message.update_form_title', ['form' => __('message.client_testimonials')]);
        $data = FrontendData::findOrFail($id);

        return view('client_testimonials.model', compact('data', 'pageTitle', 'id'));

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(ClientTestimonialRequest $request, $id)
    {
        $frontenddata = FrontendData::find($id);
        
        if($frontenddata == null) {
            $message = __('message.not_found_entry', ['name' => __('message.frontenddata')]);
            return json_custom_response(['status' => false, 'message' => $message ],400);
        }
        $data = $request->all();
        $data['type'] = 'client_testimonials';

        $frontenddata->fill($data)->update();
        
        uploadMediaFile($frontenddata,$request->frontend_data_image, 'frontend_data_image');
        
        $message = __('message.update_form',[ 'form' => __('message.frontenddata') ] );
        return response()->json(['status' => true, 'event' =>  'refresh', 'message'=> $message]);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if(env('APP_DEMO')){
            $message = __('message.demo_permission_denied');
            if(request()->is('api/*')){
                return response()->json(['status' => true, 'message' => $message ]);
            }
            if(request()->ajax()) {
                return response()->json(['status' => false, 'message' => $message, 'event' => 'validation']);
            }
            return redirect()->back()->withErrors($message);
        }
        $client_testimonials = FrontendData::find($id);
        $status = 'error';
        $message = __('message.not_found_entry', ['name' =>__('message.client_testimonials')]);

        if( $client_testimonials != '' ) {
            $status = 'success';
            $message = __('message.delete_form',['form' => __('message.client_testimonials')] );
            $client_testimonials->delete();
        } 
        
        return redirect()->back()->with($status,$message);

    }
}
