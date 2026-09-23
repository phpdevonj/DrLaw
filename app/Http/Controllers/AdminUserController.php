<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\DataTables\AdminUserDataTable;
use App\Models\Role;
use App\Http\Requests\AdminUserRequest;
use App\Models\User;

class AdminUserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(AdminUserDataTable $dataTable)
    {
        $pageTitle = __('message.list_form_title',['form' => __('message.admin_user')] );
        $auth_user = authSession();
        $assets = ['datatable'];
        $last_actived_at = request('last_actived_at') ?? null;
        $button = $auth_user->can('rider add') ? '<a href="'.route('admin-user.create').'" class="float-right btn btn-md border-radius-10 btn-primary me-2"><i class="fa fa-plus-circle"></i> '.__('message.add_form_title',['form' => __('message.admin_user')]).'</a>' : '';

        return $dataTable->render('global.datatable', compact('assets','pageTitle','button','auth_user','last_actived_at'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $pageTitle = __('message.add_form_title',[ 'form' => __('message.admin_user')]);
        $assets = ['phone'];
        return view('admin_user.form', compact('pageTitle','assets'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(AdminUserRequest $request)
    {
        $request['password'] = bcrypt($request->password);

        $request['username'] = $request->username ?? stristr($request->email, "@", true) . rand(100,1000);
        $request['display_name'] = $request->first_name.' '. $request->last_name;
        $role = Role::where('id',$request['user_type'])->first();
        $request['user_type'] = $role->name;
        
        $user = User::create($request->all());

        uploadMediaFile($user,$request->profile_image, 'profile_image');

        $user->assignRole($role->name);
        return redirect()->route('admin-user.index')->withSuccess(__('message.save_form', ['form' => __('message.admin_user')]));
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
        $pageTitle = __('message.update_form_title',[ 'form' => __('message.admin_user')]);
        $data = User::whereNotIn('user_type', ['driver','rider'])->findOrFail($id);

        $profileImage = getSingleMedia($data, 'profile_image');
        $assets = ['phone'];
        $role = Role::where('name',$data['user_type'])->first();
        $roleId = $role->id;
        return view('admin_user.form', compact('data', 'pageTitle', 'id', 'profileImage', 'assets','roleId'));
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

        if(auth()->check()){
            return redirect()->route('admin-user.index')->withSuccess(__('message.update_form',['form' => __('message.admin_user')]));
        }
        return redirect()->back()->withSuccess(__('message.update_form',['form' => __('message.admin_user') ] ));
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
            return redirect()->route('admin-user.index')->withErrors($message);
        }
        $user = User::findOrFail($id);
        $status = 'errors';
        $message = __('message.not_found_entry', ['name' => __('message.admin_user')]);

        if($user!='') {
            $user->delete();
            $status = 'success';
            $message = __('message.delete_form', ['form' => __('message.admin_user')]);
        }

        if(request()->ajax()) {
            return response()->json(['status' => true, 'message' => $message ]);
        }

        return redirect()->back()->with($status,$message);
    }
}
