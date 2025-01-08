<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\DataTables\RiderDataTable;
use App\Models\Role;
use App\Http\Requests\RiderRequest;
use App\DataTables\RideRequestDataTable;
use App\DataTables\WalletHistoryDataTable;
use App\DataTables\WithdrawRequestDataTable;

class RiderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(RiderDataTable $dataTable)
    {
        $pageTitle = __('message.list_form_title',['form' => __('message.rider')] );
        $auth_user = authSession();
        $assets = ['datatable'];
        $last_actived_at = request('last_actived_at') ?? null;
        $button = $auth_user->can('rider add') ? '<a href="'.route('rider.create').'" class="float-right btn btn-md border-radius-10 btn-primary me-2"><i class="fa fa-plus-circle"></i> '.__('message.add_form_title',['form' => __('message.rider')]).'</a>' : '';

        return $dataTable->render('global.rider-datatable', compact('assets','pageTitle','button','auth_user','last_actived_at'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $pageTitle = __('message.add_form_title',[ 'form' => __('message.rider')]);
        $assets = ['phone'];
        return view('rider.form', compact('pageTitle','assets'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(RiderRequest $request)
    {
        $request['password'] = bcrypt($request->password);

        $request['username'] = $request->username ?? stristr($request->email, "@", true) . rand(100,1000);
        $request['display_name'] = $request->first_name.' '. $request->last_name;
        $request['user_type'] = 'rider';
        $user = User::create($request->all());

        uploadMediaFile($user,$request->profile_image, 'profile_image');

        $user->assignRole('rider');
        // $user->userBankAccount()->create($request->userBankAccount);
        return redirect()->route('rider.index')->withSuccess(__('message.save_form', ['form' => __('message.rider')]));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(RideRequestDataTable $dataTable, WalletHistoryDataTable $walletHistoryDataTable, WithdrawRequestDataTable $WithdrawRequestDataTable, $id)
    {
        $pageTitle = __('message.view_form_title',[ 'form' => __('message.rider')]);
        $data = User::where('user_type', 'rider')->with('roles','userBankAccount')->findOrFail($id);
        $data->rating = count($data->riderRating) > 0 ? (float) number_format(max($data->riderRating->avg('rating'),0), 2) : 0;

        $profileImage = getSingleMedia($data, 'profile_image');

        $type = request('type') ?? 'detail';
        switch ($type) {
            case 'detail':
                    return view('rider.show', compact('pageTitle', 'data', 'profileImage','type'));
                break;
                
            case 'wallet_history':
                    return $walletHistoryDataTable->with('user_id',$id)->render('rider.show', compact('pageTitle', 'data', 'type'));
                break;
            
            case 'ride_request':
                    return $dataTable->with('rider_id',$id)->render('rider.show', compact('pageTitle', 'data', 'type'));
                break;

            case 'withdraw_request':
                    return $WithdrawRequestDataTable->with('rider_id',$id)->render('rider.show', compact('pageTitle', 'data', 'type'));
                break;
            
            default:
                # code...
                $type = 'detail';
                return view('rider.show', compact('pageTitle', 'data', 'profileImage','type'));
                break;
        }


    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $pageTitle = __('message.update_form_title',[ 'form' => __('message.rider')]);
        $data = User::where('user_type', 'rider')->with('userBankAccount')->findOrFail($id);

        $profileImage = getSingleMedia($data, 'profile_image');
        $assets = ['phone'];
        return view('rider.form', compact('data', 'pageTitle', 'id', 'profileImage', 'assets'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(RiderRequest $request, $id)
    {
        $user = User::findOrFail($id);

        $request['password'] = $request->password != '' ? bcrypt($request->password) : $user->password;

        $request['display_name'] = $request->first_name.' '. $request->last_name;
        // User user data...
        $user->fill($request->all())->update();

        // Save user image...
        if (isset($request->profile_image) && $request->profile_image != null) {
            $user->clearMediaCollection('profile_image');
            $user->addMediaFromRequest('profile_image')->toMediaCollection('profile_image');
        }

        if($user->userBankAccount != null) {
            $user->userBankAccount->fill($request->userBankAccount)->update();
        } else {
            $user->userBankAccount()->create($request->userBankAccount);
        }

        if(auth()->check()){
            return redirect()->route('rider.index')->withSuccess(__('message.update_form',['form' => __('message.rider')]));
        }
        return redirect()->back()->withSuccess(__('message.update_form',['form' => __('message.rider') ] ));
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
            return redirect()->route('rider.index')->withErrors($message);
        }
        $user = User::findOrFail($id);
        $status = 'errors';
        $message = __('message.not_found_entry', ['name' => __('message.rider')]);

        if($user!='') {
            $user->delete();
            $status = 'success';
            $message = __('message.delete_form', ['form' => __('message.rider')]);
        }

        if(request()->ajax()) {
            return response()->json(['status' => true, 'message' => $message ]);
        }

        return redirect()->back()->with($status,$message);
    }
}
