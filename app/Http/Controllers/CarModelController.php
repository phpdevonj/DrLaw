<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CarModel;
use App\DataTables\CarModelDataTable;

class CarModelController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(CarModelDataTable $dataTable)
    {
        $pageTitle = __('message.list_form_title',['form' => __('message.car_model')] );
        $auth_user = authSession();
        $assets = ['datatable'];
        $button = $auth_user->can('sos add') ? '<a href="'.route('car-models.create').'" class="float-right btn btn-md border-radius-10 btn-outline-dark"><i class="fa fa-plus-circle"></i> '.__('message.add_form_title',['form' => __('message.car_model')]).'</a>' : '';
        return $dataTable->render('global.datatable', compact('pageTitle','button','auth_user'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $pageTitle = __('message.add_form_title',[ 'form' => __('message.car_model')]);
        
        return view('car_model.form', compact('pageTitle'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $car_model = CarModel::create($request->all());

        $message = __('message.save_form',['form' => __('message.car_model')]);
        
        if(request()->is('api/*')){
            return json_message_response( $message );
        }

        return redirect()->route('car-models.index')->withSuccess($message);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $pageTitle = __('message.update_form_title',[ 'form' => __('message.car_model')]);
        $data = CarModel::findOrFail($id);
        
        return view('car_model.form', compact('data', 'pageTitle', 'id'));
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
        $car_model = CarModel::findOrFail($id);

        // car_model data...
        $car_model->fill($request->all())->update();

        $message = __('message.update_form',['form' => __('message.car_model')]);

        if(request()->is('api/*')){
            return json_message_response( $message );
        }

        if(auth()->check()){
            return redirect()->route('car-models.index')->withSuccess($message);
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
            return redirect()->route('car-models.index')->withErrors($message);
        }
        $car_model = CarModel::find($id);
        $status = 'errors';
        $message = __('message.not_found_entry', ['name' => __('message.car_model')]);

        if($car_model != '') {
            $car_model->delete();
            $status = 'success';
            $message = __('message.delete_form', ['form' => __('message.car_model')]);
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
