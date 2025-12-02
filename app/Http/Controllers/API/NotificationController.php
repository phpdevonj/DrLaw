<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Models\Notification;
use App\Http\Controllers\Controller;

use App\Http\Resources\NotificationResource;

class NotificationController extends Controller
{
    public function getList(Request $request)
    {
        $user = auth()->user();

        $user->last_notification_seen = now();
        $user->save();

        $type = isset($request->type) ? $request->type : null;
        if($type == "markas_read"){
            if(count($user->unreadNotifications) > 0 ) {
                $user->unreadNotifications->markAsRead();
            }
        }
        
        $page = isset($request->page) ? $request->page : 1;
        $limit = isset($request->limit) ? $request->limit : config('constant.PER_PAGE_LIMIT');
        // $limit = config('constant.PER_PAGE_LIMIT');

        $notifications = $user->Notifications->sortByDesc('created_at')->forPage($page,$limit);

        $all_unread_count = isset($user->unreadNotifications) ? $user->unreadNotifications->count() : 0;

        $items = NotificationResource::collection($notifications);
        
        $response = [
            'notification_data' => $items,
            'all_unread_count' => $all_unread_count,
        ];

        return json_custom_response($response);
    }

    public function notificationCounts(Request $request)
    {
        $user = auth()->user();

        $unread_count = 0;
        $unread_total_count = 0;

        if(isset($user->unreadNotifications)){
            $unread_count = $user->unreadNotifications->where('created_at', '>', $user->last_notification_seen)->count() ;
            $unread_total_count = $user->unreadNotifications->count();
        }
        $response = [
            'status'            => true,
            'counts'            => $unread_count,
            'unread_total_count'=> $unread_total_count
        ];

        return json_custom_response($response);
    }

    public function markAsRead($id)
    {
        $notification = Notification::where('id', $id)->where('notifiable_id', auth()->id())->first();

        if (!$notification) {
            return json_message_response('Notification not found',400);
        }

        $notification->update(['read_at' => now()]);

        $response = [
            'status'  => true,
            'message' => 'Notification marked as read.'
        ];

        return json_custom_response($response);
    }

    public function markAllAsRead()
    {
        $user = auth()->user();

        if(isset($user->unreadNotifications)){
            $user->unreadNotifications->markAsRead();

            $response = [
                'status'  => true,
                'message' => 'All notifications marked as read.'
            ];
    
            return json_custom_response($response);
        }else{
            return json_message_response('No unread notifications found',400);
        }        
    }
}
