<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PushNotification;
use App\DataTables\PushNotificationDataTable;
use App\Models\User;
use App\Notifications\CommonNotification;
use App\Notifications\RideNotification;
use App\Models\Notification;

class PushNotificationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(PushNotificationDataTable $dataTable)
    {
        $pageTitle = __('message.list_form_title',['form' => __('message.pushnotification')] );
        $auth_user = authSession();
        $assets = ['datatable'];
        $button = $auth_user->can('pushnotification add') ? '<a href="'.route('pushnotification.create').'" class="float-right btn btn-md border-radius-10 btn-outline-dark"><i class="fa fa-plus-circle"></i> '.__('message.add_form_title',['form' => __('message.pushnotification')]).'</a>' : '';
        return $dataTable->render('global.datatable', compact('pageTitle','button','auth_user'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $pageTitle = __('message.add_form_title',[ 'form' => __('message.pushnotification')]);
        $relation = [
            'rider' => User::where('user_type','rider')->where('status','active')->get()->pluck('display_name', 'id'),
            'driver' => User::where('user_type','driver')->where('status','active')->get()->pluck('display_name', 'id'),
        ];
        
        return view('push_notification.form', compact('pageTitle')+$relation);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $pushnotification = PushNotification::create($request->all());

        uploadMediaFile($pushnotification, $request->notification_image, 'notification_image');
        
        $notification_data = [
            'id' => $pushnotification->id,
            'push_notification_id' => $pushnotification->id,
            'type' => 'push_notification',
            'subject' => $pushnotification->title,
            'message' => $pushnotification->message,
        ];
        if( getMediaFileExit($pushnotification, 'notification_image') ) {
            $notification_data['image'] = getSingleMedia($pushnotification, 'notification_image');
        } else {
            $notification_data['image'] = null;
        }

        if ($request->has('driver')) {
            $pushnotification->update(['for_driver' => 1]);

            User::whereIn('id', $request->driver)->chunk(20, function ($driverdata) use ($notification_data) {
                foreach ($driverdata as $user) {
                    $user->notify(new CommonNotification($notification_data['type'], $notification_data));
                    $user->notify(new RideNotification($notification_data));
                }
            });
        }

        if ($request->has('rider')) {
            $pushnotification->update(['for_rider' => 1]);

            User::whereIn('id', $request->rider)->chunk(20, function ($riderdata) use ($notification_data) {
                foreach ($riderdata as $user) {
                    $user->notify(new CommonNotification($notification_data['type'], $notification_data));
                    $user->notify(new RideNotification($notification_data));
                }
            });
        }
        
        return redirect()->route('pushnotification.index')->withSuccess(__('message.save_form', ['form' => __('message.pushnotification')]));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $pageTitle = __('message.update_form_title',[ 'form' => __('message.pushnotification')]);
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
            return redirect()->route('pushnotification.index')->withErrors($message);
        }
        $pushnotification = PushNotification::findOrFail($id);
        $status = 'errors';
        $message = __('message.not_found_entry', ['name' => __('message.pushnotification')]);

        if($pushnotification != '') {
            // $search = "push_notification_id".'":'.$id;
            // Notification::where('data','like',"%{$search}%")->delete();
            Notification::whereJsonContains('data->push_notification_id',$id);
            $pushnotification->delete();
            $status = 'success';
            $message = __('message.delete_form', ['form' => __('message.pushnotification')]);
        }

        if(request()->ajax()) {
            return response()->json(['status' => true, 'message' => $message ]);
        }

        return redirect()->back()->with($status, $message);
    }
}
