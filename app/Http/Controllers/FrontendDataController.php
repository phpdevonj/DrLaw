<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FrontendData;

class FrontendDataController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $data = $request->all();

        $result = FrontendData::updateOrCreate(['id' => $request->id], $data);

        uploadMediaFile($result, $request->frontend_data_image, 'frontend_data_image');

        $type = $result->type ? __('message.'.$result->type) : __('message.record');

        $message = __('message.update_form',[ 'form' => $type ] );
		if($result->wasRecentlyCreated){
			$message = __('message.save_form',[ 'form' => $type ] );
		}

        if($request->is('api/*')) {
            return json_message_response($message);
		}
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
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $frontend_data = FrontendData::find($id);

        if( $frontend_data != null ) {
            $message = __('message.msg_deleted',['name' => __('message.'.$frontend_data->type)] );
            $frontend_data->delete();
        } else {
            $type = __('message.record');
            $message = __('message.msg_fail_to_delete',['item' =>  $type ] );
        }

        if(request()->is('api/*')) {
            return json_custom_response(['message' => $message, 'status' => true]);
        }
    }
}
