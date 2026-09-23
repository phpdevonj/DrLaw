<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Berkayk\OneSignal\OneSignalClient;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Factory;
use App\Models\AppSetting;

class DocumentExpiryNotification extends Notification
{
    use Queueable;

    public $driverDocument;
    public $daysLeft;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($driverDocument, $daysLeft)
    {
        $this->driverDocument = $driverDocument;
        $this->daysLeft = $daysLeft;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        $channels = ['mail'];

        // Send FCM to riders and drivers manually
        if($notifiable->fcm_token && $notifiable->user_type == 'driver') {
            $this->sendFcm($notifiable);
        }

        return $channels;
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
            // env() returns null here once config is cached (php artisan config:cache),
            // since Laravel only evaluates env() while building config/*.php. Read the
            // credentials path via config('firebase...') instead, which stays correct
            // whether or not config is cached, and matches config/firebase.php's own
            // resolution of FIREBASE_CREDENTIALS.
            $credentialsFile = config('firebase.projects.app.credentials.file');
            $credentialsPath = $credentialsFile ? base_path($credentialsFile) : null;

            if (!$credentialsPath || !is_file($credentialsPath)) {
                Log::channel('firebase_notification')->error("Firebase credentials file not found at " . ($credentialsPath ?? '(not configured)'));
                return;
            }

            $factory = (new Factory)
                ->withServiceAccount($credentialsPath);
            $messaging = $factory->createMessaging();

            // Common title & message
            $title = 'Document Expiry Reminder';
            $body  = strip_tags($this->getNotificationMessage());

            $payload = [
                'message' => [
                    'token' => $notifiable->fcm_token,
                    'android' => [
                        'priority' => 'HIGH',
                    ],
                    'apns' => [
                        'payload' => [
                            'aps' => [
                                'category' => 'GENERAL',
                                //'mutable-content' => 1,
                                'alert' => [
                                    'title' => $title,
                                    'body'  => $body,
                                ],
                                'sound' => 'onecab_alert.caf',
                            ],
                        ],
                    ]
                ],
            ];

            $payload['message']['data'] = [
                'title' => $title,
                'body'  => $body,
                'type'  => (string)($this->data['type'] ?? ''),
                'id'    => (string)($this->data['id'] ?? ''),
            ];

            $messaging->send($payload['message']);

        } catch (\Throwable $e) {
            Log::channel('firebase_notification')->error("❌ FCM send failed for user {$notifiable->id}: " . $e->getMessage());
        }
    }

    /**
     * Send OneSignal Notification manually (similar to CommonNotification logic)
     */
    protected function sendOneSignalNotification($notifiable)
    {
        $message = $this->getNotificationMessage();
        
        $heading = [
            'en' => __('message.document_expiry_reminder'),
        ];

        $content = [
            'en' => $message,
        ];
        
        $parameters = [
            'api_key' => env('ONESIGNAL_DRIVER_REST_API_KEY'),
            'app_id' => env('ONESIGNAL_DRIVER_APP_ID'),
            'include_player_ids' => [$notifiable->player_id],
            'headings' => $heading,
            'contents' => $content,
            'data'  => [
                'id' => $this->driverDocument->id,
                'type' => 'document_expiry',
                'document_id' => $this->driverDocument->document_id,
            ]
        ];

        try {
            $onesignal_client = new OneSignalClient(env('ONESIGNAL_DRIVER_APP_ID'), env('ONESIGNAL_DRIVER_REST_API_KEY') , null );
            $onesignal_client->sendNotificationCustom($parameters);
        } catch (\Exception $e) {
            Log::error('Failed to send OneSignal notification: ' . $e->getMessage());
        }
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
  
        $app_setting = AppSetting::first();
        $logoUrl = getSingleMedia($app_setting, 'site_logo', false);
        $message = $this->getNotificationMessage();
         return (new MailMessage)
            ->subject('🚨 Action Required: Document Expired Today')
            ->markdown('emails.document_expiry', [
                'notifiable' => $notifiable,
                'doc' => $this->driverDocument,
                'daysLeft' => $this->daysLeft,
                'message' => $message,
                'logoUrl' => $logoUrl,
            ]);
    }

    protected function getNotificationMessage()
    {
        $docName = $this->driverDocument->document->name;
        
        if ($this->daysLeft == 0) {
            return "Your document '{$docName}' has expired today.";
        } elseif ($this->daysLeft < 0) {
             return "Your document '{$docName}' expired.";
        } else {
            return "Your document '{$docName}' will expire in {$this->daysLeft} days.";
        }
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
            'document_id' => $this->driverDocument->document_id,
            'expire_date' => $this->driverDocument->expire_date,
            'days_left' => $this->daysLeft,
            'message' => $this->getNotificationMessage(),
        ];
    }
}
