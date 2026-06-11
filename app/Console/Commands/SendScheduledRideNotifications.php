<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\RideRequest;
use App\Notifications\CommonNotification;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class SendScheduledRideNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scheduleride:send-notifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send notifications for scheduled rides';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->sendReminders(24);
        $this->sendReminders(1);
    }

    protected function sendReminders($hours){
        $column = $hours === 24 ? 'last_24hr_notification_sent_at' : 'last_1hr_notification_sent_at';

        Log::info("scheduled_at - Start: ".Carbon::now()->addHours($hours)->subMinutes(15).", End: ".Carbon::now()->addHours($hours));
        $rides = RideRequest::where('is_schedule', 1)
                ->whereIn('status', ['scheduled', 'driver_accepted'])
                ->whereNull($column)
                ->whereBetween('scheduled_at', [
                    Carbon::now()->addHours($hours)->subMinutes(15),
                    Carbon::now()->addHours($hours)
                ])
                ->get();
        
        foreach ($rides as $ride) {
            $this->sendReminderNotification($ride, $hours);
            $ride->update([$column => Carbon::now()->toDateTimeString()]); // Mark notification as sent
        }
    }

    protected function sendReminderNotification($ride, $hours){
        $message = "You have a scheduled ride coming up in $hours hours.";
        $subject = 'Upcoming Scheduled Ride';
        $type = 'scheduled_ride_reminder';

        // Send notification to rider
        if ($rider = User::find($ride->rider_id)) {            
            $rider_notification_data = [
                'id' => $ride->id,
                'type' => $type,
                'data' => $ride->driver_id ? ['driver_name' => optional($ride->driver)->display_name] : [],
                'message' => $message,
                'subject' => $subject,
            ];
            $rider->notify(new CommonNotification($rider_notification_data['type'], $rider_notification_data));
        }

        // Send notification to driver
        if ($ride->driver_id) {
            $driver_notification_data = [
                'id' => $ride->id,
                'type' => $type,
                'data' => [
                    'rider_id' => $ride->rider_id ?? '',
                    'rider_name' => optional($ride->rider)->display_name ?? '',
                ],
                'message' => $message,
                'subject' => $subject,
            ];

            if ($driver = User::find($ride->driver_id)) {
                $driver->notify(new CommonNotification($driver_notification_data['type'], $driver_notification_data));
            }
        }
    }
}