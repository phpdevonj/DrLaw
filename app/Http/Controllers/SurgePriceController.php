<?php

namespace App\Http\Controllers;

use App\DataTables\SurgePriceDataTable;
use App\Http\Requests\SurgePriceRequest;
use App\Models\SurgePrice;
use Illuminate\Http\Request;

class SurgePriceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(SurgePriceDataTable $dataTable)
    {
        $pageTitle = __('message.list_form_title',['form' => __('message.surge_price')] );
        $auth_user = authSession();
        $assets = ['datatable'];
        // $button = $auth_user->can('surgeprice add') ? '<a href="'.route('surge-prices.create').'" class="float-right btn btn-sm btn-primary"><i class="fa fa-plus-circle"></i> '.__('message.add_form_title',['form' => __('message.surge_price')]).'</a>' : '';
        $button ='<a href="'.route('surge-prices.create').'" class="float-right btn btn-md border-radius-10 btn-outline-dark"><i class="fa fa-plus-circle"></i> '.__('message.add_form_title',['form' => __('message.surge_price')]).'</a>';
        return $dataTable->render('global.datatable', compact('pageTitle','button','auth_user'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $pageTitle = __('message.add_form_title',[ 'form' => __('message.surge_price')]);
        
        return view('surge_price.form', compact('pageTitle'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(SurgePriceRequest $request)
    {
        SurgePrice::create($request->all());

        $message = __('message.save_form',['form' => __('message.surge_price')]);
        
        if(request()->is('api/*')){
            return json_message_response( $message );
        }

        return redirect()->route('surge-prices.index')->withSuccess($message);
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
        $pageTitle = __('message.update_form_title',[ 'form' => __('message.surge_price')]);
        $data = SurgePrice::findOrFail($id);
        
        if (is_string($data->from_time)) {
            $data->from_time = json_decode($data->from_time, true);
        }

        if (is_string($data->to_time)) {
            $data->to_time = json_decode($data->to_time, true);
        }

        return view('surge_price.form', compact('data', 'pageTitle', 'id'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(SurgePriceRequest $request, $id)
    {
        $surge_price = SurgePrice::findOrFail($id);

        $surge_price->fill($request->all())->update();

        $message = __('message.update_form',['form' => __('message.surge_price')]);

        if(request()->is('api/*')){
            return json_message_response( $message );
        }

        if(auth()->check()){
            return redirect()->route('surge-prices.index')->withSuccess($message);
        }
        return redirect()->back()->withSuccess($message);
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
            if(request()->ajax()) {
                return response()->json(['status' => true, 'message' => $message ]);
            }
            return redirect()->route('surge-prices.index')->withErrors($message);
        }
        $surge_price = SurgePrice::find($id);
        $status = 'errors';
        $message = __('message.not_found_entry', ['name' => __('message.surge_price')]);

        if($surge_price != '') {
            $surge_price->delete();
            $status = 'success';
            $message = __('message.delete_form', ['form' => __('message.surge_price')]);
        }
        
        if(request()->is('api/*')){
            return json_message_response( $message );
        }

        if(request()->ajax()) {
            return response()->json(['status' => true, 'message' => $message ]);
        }

        return redirect()->back()->with($status,$message);
    }
}
