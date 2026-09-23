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
use App\DataTables\PointHistoryDataTable;
use App\DataTables\UserAddressDataTable;
use Carbon\Carbon;

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
        $plainPassword = $request->password;
        $request['password'] = bcrypt($request->password);

        $request['username'] = $request->username ?? stristr($request->email, "@", true) . rand(100,1000);
        $request['display_name'] = $request->first_name.' '. $request->last_name;
        $request['user_type'] = 'rider';

        $sibling = User::where('email', $request->email ?? '')->first();
        if ($sibling) {
            $request['password'] = $sibling->password;
        }

        // Create a customer in Stripe using the helper function (only if Stripe is configured in DB)
        if (hasStripeKeys()) {
            if ($sibling && $sibling->stripe_customer_id) {
                $request['stripe_customer_id'] = $sibling->stripe_customer_id;
            } else {
                $stripeCustomer = createStripeCustomer($request->email ?? '', $request->display_name, $request->contact_number, 'stripe');
                if (isset($stripeCustomer['id'])) {
                    $request['stripe_customer_id'] = $stripeCustomer['id'];
                } else {
                    return redirect()->back()->withErrors('Failed to create Stripe customer.');
                }
            }
        }

        // Create Firebase user
        $auth = app('firebase.auth');
        if ($sibling && $sibling->uid) {
            $uid = $sibling->uid;
        } else {
            try {
                $firebaseUser = $auth->createUser([
                    'email' => $request->email,
                    'password' => $plainPassword,
                ]); 
            } catch (\Exception $e) {
                if (strpos($e->getMessage(), 'EMAIL_EXISTS') !== false) {
                    $firebaseUser = $auth->getUserByEmail($request->email);
                } else {
                    return redirect()->back()->withErrors('Firebase Error: ' . $e->getMessage());
                }
            }
            $uid = $firebaseUser->uid;
        }

        // Store data in Firestore
        $firestore = app('firebase.firestore');
        $collection = $firestore->database()->collection('users');
        $document = $collection->document($uid)->set([
            "contact_number" => $request->contact_number,
            "created_at" => Carbon::parse($request->created_at)->format('Y-m-d H:i:s.u'),
            "display_name" => $request->display_name,
            "email" => $request->email,
            "first_name" => $request->first_name,
            "last_name" => $request->last_name,
            "player_id" => null,
            "uid" => $uid,
            "updated_at" => Carbon::parse($request->updated_at)->format('Y-m-d H:i:s.u'),
            "user_type" => $request->user_type,
            "username" => $request->username,
        ]);
        $request['uid'] = $uid;

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
    public function show(RideRequestDataTable $dataTable, WalletHistoryDataTable $walletHistoryDataTable, WithdrawRequestDataTable $WithdrawRequestDataTable, PointHistoryDataTable $pointHistoryDataTable, UserAddressDataTable $userAddressDataTable, $id)
    {
        $pageTitle = __('message.view_form_title',[ 'form' => __('message.rider')]);
        $data = User::where('user_type', 'rider')->with('roles','userBankAccount')->findOrFail($id);
        $data->rating = count($data->riderRating) > 0 ? (float) number_format(max($data->riderRating->avg('rating'),0), 2) : 0;

        $profileImage = getSingleMedia($data, 'profile_image');

        $loyalty_program = SettingData('ride', 'loyalty_program') ?? 0;

        $type = request('type') ?? 'detail';
        switch ($type) {
            case 'detail':
                    return view('rider.show', compact('pageTitle', 'data', 'profileImage','type','loyalty_program'));
                break;
                
            case 'wallet_history':
                    return $walletHistoryDataTable->with('user_id',$id)->render('rider.show', compact('pageTitle', 'data', 'type','loyalty_program'));
                break;

            case 'points_history':
                    return $pointHistoryDataTable->with('user_id',$id)->render('rider.show', compact('pageTitle', 'data', 'type','loyalty_program'));
                break;
            
            case 'ride_request':
                    return $dataTable->with('rider_id',$id)->render('rider.show', compact('pageTitle', 'data', 'type','loyalty_program'));
                break;

            case 'withdraw_request':
                    return $WithdrawRequestDataTable->with('rider_id',$id)->render('rider.show', compact('pageTitle', 'data', 'type','loyalty_program'));
                break;
            case 'address':
                return $userAddressDataTable->with('user_id',$id)->render('rider.show', compact('pageTitle', 'data', 'type','loyalty_program'));
            break;
            default:
                # code...
                $type = 'detail';
                return view('rider.show', compact('pageTitle', 'data', 'profileImage','type','loyalty_program'));
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
        return view('rider.form', compact('data', 'pageTitle', 'id', 'profileImage','assets'));
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

        // Check if email, name, or phone number has changed
        $emailChanged = $user->email !== $request->email;
        $nameChanged = $user->display_name !== $request->first_name . ' ' . $request->last_name;
        $phoneChanged = $user->contact_number !== $request->contact_number;

        if ($emailChanged || $nameChanged || $phoneChanged) {
            // Update Stripe customer (only if Stripe keys are configured in DB)
            if (hasStripeKeys() && $user->stripe_customer_id) {
                $stripeResponse = updateStripeCustomer($user->stripe_customer_id, $request->email, $request->first_name . ' ' . $request->last_name, $request->contact_number);
                if (isset($stripeResponse['error'])) {
                    return redirect()->back()->withErrors('Failed to update Stripe customer.');
                }
            }
        }

        // User user data...
        $user->fill($request->all())->update();
        syncSharedFieldsToSiblings($user, ['first_name', 'last_name', 'display_name', 'password']);

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
            $siblings = getSiblingAccounts($user);
            // Delete Stripe customer (only if Stripe keys are configured in DB)
            if ($siblings->isEmpty() && hasStripeKeys() && $user->stripe_customer_id) {
                $stripeResponse = deleteStripeCustomer($user->stripe_customer_id);
                if (isset($stripeResponse['error'])) {
                    return redirect()->back()->withErrors('Failed to delete Stripe customer.');
                }
            }
            $user->delete();
            // delete from firebase
            if($siblings->isEmpty() && $user->uid != null){
                $firebaseData = app('firebase.firestore')->database()->collection('users')->document($user->uid);
                $firebaseData->delete();
            } 
            $status = 'success';
            $message = __('message.delete_form', ['form' => __('message.rider')]);
        }

        if(request()->ajax()) {
            return response()->json(['status' => true, 'message' => $message ]);
        }

        return redirect()->back()->with($status,$message);
    }
}
