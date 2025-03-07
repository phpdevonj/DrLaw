<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserAddress;

class UserAddressController extends Controller
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
        $request->validate([
            'address_type' => 'required|in:home,work,other,custom',
            'custom_label' => 'required_if:address_type,custom',
            'street_address' => 'required|string',
            'start_latitude' => 'required|numeric',
            'start_longitude' => 'required|numeric',
        ]);

        try {
            $address = UserAddress::create([
                'user_id' => $request->user_id,
                'label' => $request->address_type,
                'custom_label' => $request->custom_label,
                'address_line1' => $request->street_address,
                'latitude' => $request->start_latitude,
                'longitude' => $request->start_longitude,
            ]);
            return redirect()->back()->with('success', __('message.save_form', ['form' => __('message.address')]));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
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
        $pageTitle = __('message.update_form_title',[ 'form' => __('message.address')]);
        $data = UserAddress::findOrFail($id);
        
        return view('user_address.form', compact('data', 'pageTitle', 'id'));
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

        $user_address = UserAddress::findOrFail($id);

        $request->validate([
            'address_type' => 'required|in:home,work,other,custom',
            'custom_label' => 'required_if:address_type,custom',
            'street_address' => 'required|string',
            'start_latitude' => 'required|numeric',
            'start_longitude' => 'required|numeric',
        ]);

        try {
            $data = [
                'user_id' => $request->user_id,
                'label' => $request->address_type,
                'custom_label' => $request->custom_label,
                'address_line1' => $request->street_address,
                'latitude' => $request->start_latitude,
                'longitude' => $request->start_longitude,
            ];

            $user_address->fill($data)->update();
            return redirect()->to(url('rider/'.$request->user_id.'?type=address'))->with('success', __('message.update_form', ['form' => __('message.address')]));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $user_address = UserAddress::findOrFail($id);
        $status = 'errors';
        $message = __('message.not_found_entry', ['name' => __('message.address')]);

        if($user_address != '') {
            $user_address->delete();
            $status = 'success';
            $message = __('message.delete_form', ['form' => __('message.address')]);
        }

        if(request()->ajax()) {
            return response()->json(['status' => true, 'message' => $message ]);
        }
        return redirect()->back()->with($status,$message);
    }
}
