<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\AppSetting;
use NotificationChannels\OneSignal\OneSignalChannel;
use NotificationChannels\OneSignal\OneSignalMessage;
use Illuminate\Support\Facades\Log;
//use Benwilkins\FCM\FcmMessage;
use Berkayk\OneSignal\OneSignalClient;
use Kreait\Firebase\Factory;

class CommonNotification extends Notification
{
    use Queueable;
    public $type, $data, $subject, $notification_message;
    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($type, $data)
    {
        $this->type = $type;
        $this->data = $data;
        $this->subject = str_replace("_"," ",ucfirst($this->data['subject']));
        $this->notification_message = $this->data['message'] != '' ? $this->data['message'] : __('message.default_notification_body');
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        // Send FCM to riders and drivers manually
        if($notifiable->fcm_token && $notifiable->user_type == 'driver') {
            $this->sendFcm($notifiable);
        }

        $notifications = []; 

        if( $notifiable->player_id != null && $notifiable->user_type == 'rider') {
            // if( $notifiable->user_type == 'driver' && env('ONESIGNAL_DRIVER_APP_ID') && env('ONESIGNAL_DRIVER_REST_API_KEY')) 
            // {
            //         $heading = [
            //             'en' => $this->subject,
            //         ];
            
            //         $content = [
            //             'en' => strip_tags($this->notification_message),
            //         ];
                    
            //         $parameters = [
            //             'api_key' => env('ONESIGNAL_DRIVER_REST_API_KEY'),
            //             'app_id' => env('ONESIGNAL_DRIVER_APP_ID'),
            //             'include_player_ids' => [$notifiable->player_id],
            //             'headings' => $heading,
            //             'contents' => $content,
            //             'data'  => [
            //                 'id' => $this->data['id'],
            //                 'type' => $this->data['type'],
            //             ]
            //         ];

            //         if( $this->type == 'push_notification' && $this->data['image'] != null ) {
            //             $parameters['big_picture'] = $this->data['image'];
            //             $parameters['ios_attachments'] = $this->data['image'];
            //         }

            //         // Log::channel('firebase_notification')->info('driver-notifiable-'.$notifiable);
            //         $onesignal_client = new OneSignalClient(env('ONESIGNAL_DRIVER_APP_ID'), env('ONESIGNAL_DRIVER_REST_API_KEY') , null );
            //         $onesignal_client->sendNotificationCustom($parameters);
            // } else {
                array_push($notifications, OneSignalChannel::class);
            //}
        }

        // // Log::channel('firebase_notification')->info('notifiable-'.$notifiable);
        // if( env('FIREBASE_SERVER_KEY') && $notifiable->user_type == 'rider' && $notifiable->fcm_token != null ) {
        //     array_push($notifications, 'fcm');
        // }
        return $notifications;
    }

    /**
     * Send FCM notification via Kreait to riders.
     */
    protected function sendFcm($notifiable)
    {
        if (empty($notifiable->fcm_token)) {
            Log::channel('firebase_notification')->warning("⚠️ No FCM token for user {$notifiable->id}");
            return;
        }

        try {
            $factory = (new Factory)
                ->withServiceAccount(base_path(env('FIREBASE_CREDENTIALS')));
            $messaging = $factory->createMessaging();

            // Common title & message
            $title = $this->subject ?? 'Notification';
            $body  = strip_tags($this->notification_message ?? '');
            $image = $this->data['image'] ?? null;

            $payload = [
                'message' => [
                    'token' => $notifiable->fcm_token,
                    'android' => [
                        'priority' => 'HIGH',
                    ],
                    // 'apns' => [
                    //     'headers' => [
                    //         'apns-priority' => '10',
                    //     ],
                    // ],
                    'apns' => [
                        'payload' => [
                            'aps' => [
                                'category' => $this->data['type'] === 'new_ride_requested' ? 'RIDE_NOTIFICATION_CATEGORY' : 'GENERAL',
                                //'mutable-content' => 1,
                                'alert' => [
                                    'title' => $title,
                                    'body'  => $body,
                                ],
                                'sound' => 'wayvers_alert.caf',
                            ],
                        ],
                    ]
                ],
            ];

            /**
             * Case 1: New ride request → send detailed ride payload
             */
            if ($this->data['type'] === 'new_ride_requested') {
                $buttons = [
                    ['id' => 'accept_ride', 'text' => 'Accept'],
                    ['id' => 'decline_ride', 'text' => 'Decline'],
                ];

                $payload['message']['data'] = [
                    'title' => $title,
                    'body'  => $body,
                    'type'  => (string)($this->data['type'] ?? 'new_ride_requested'),
                    'id'    => (string)($this->data['id'] ?? ''),
                    'amount' => (string)($this->data['amount'] ?? ''),
                    'distance_time' => (string)($this->data['distance_time'] ?? ''),
                    'estimate' => (string)($this->data['estimate'] ?? ''),
                    'sound' => (string)($this->data['sound'] ?? 'wayvers_alert'),
                    'channel_id' => (string)($this->data['channel_id'] ?? '8255b561-8321-4bb1-a875-d2dfabbf848b'),
                    'buttons' => json_encode($buttons),
                ];
            }else {
                /**
                 * Case 2: Any other notification → simple title/body/type/id
                 */
                $payload['message']['data'] = [
                    'title' => $title,
                    'body'  => $body,
                    'type'  => (string)($this->data['type'] ?? ''),
                    'id'    => (string)($this->data['id'] ?? ''),
                    'image' => $image ?? '',
                ];
            }

            //Log::channel('firebase_notification')->info('Payload: ' . json_encode($payload));
            $messaging->send($payload['message']);
            //Log::channel('firebase_notification')->info("✅ FCM notification sent to user {$notifiable->id} ({$this->data['type']})");

        } catch (\Throwable $e) {
            Log::channel('firebase_notification')->error("❌ FCM send failed for user {$notifiable->id}: " . $e->getMessage());
        }
    }

    public function toOneSignal($notifiable)
    {
        $msg = strip_tags($this->notification_message);
        if (!isset($msg) && $msg == ''){
            $msg = __('message.default_notification_body');
        }

        $type = 'new_ride_requested';
        if (isset($this->data['type']) && $this->data['type'] !== ''){
            $type = $this->data['type'];
        }

        // Log::channel('firebase_notification')->info('onesignal notifiable'.json_encode($this->data));
        if( $type == 'push_notification' && $this->data['image'] != null ) {

            return OneSignalMessage::create()
                ->setSubject($this->subject)
                ->setBody($msg) 
                ->setData('id',$this->data['id'])
                ->setData('type',$type)
                ->setIosAttachment($this->data['image'])
                ->setAndroidBigPicture($this->data['image']);
        } else {
        return OneSignalMessage::create()
            ->setSubject($this->subject)
            ->setBody($msg) 
            ->setData('id',$this->data['id'])
            ->setData('type',$type);
        }
    }

    public function toFcm($notifiable)
    {
        $message = new FcmMessage();
        $msg = strip_tags($this->notification_message);
        if (!isset($msg) && $msg == ''){
            $msg = __('message.default_notification_body');
        }
        $notification = [
            'body' => $msg,
            'title' => $this->subject,
        ];
        $data = [
            'click_action' => "FLUTTER_NOTIFICATION_CLICK",
            'sound' => 'default',
            'status' => 'done',
            'id' => $this->data['id'],
            'type' => $this->data['type'],
            'message' => $notification,
        ];
        // Log::channel('firebase_notification')->info('fcm notifiable'.json_encode($notifiable));
        $message->content($notification)->data($data)->priority(FcmMessage::PRIORITY_HIGH);

        return $message;
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->line('The introduction to the notification.')
                    ->action('Notification Action', url('/'))
                    ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
