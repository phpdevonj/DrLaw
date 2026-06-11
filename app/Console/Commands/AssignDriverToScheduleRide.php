<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\RideRequest;
use App\Models\User;
use App\Models\Setting;
use App\Notifications\CommonNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AssignDriverToScheduleRide extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scheduleride:assign-drivers-for-schedule-rides';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign drivers to schedule ride requests';

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
        $radius = Setting::where('type', 'DISTANCE')->where('key', 'DISTANCE_RADIUS')->value('value') ?? 50; // Default radius
        $min_amount = Setting::where('key', 'min_amount_to_get_ride')->value('value') ?? null;

        // "Radius: $radius=1000 , Min amount: $min_amount=null"

        $schedule_ride_requests = RideRequest::where('is_schedule', 1)->where('status', 'scheduled')->where('scheduled_at', '<=', Carbon::now()->addMinutes(30))->where('scheduled_at', '>', Carbon::now())->where('ride_attempt', '<=', 5)->whereNull('riderequest_in_driver_id')->whereNull('riderequest_in_datetime')->whereNull('driver_id')->get();

        foreach ($schedule_ride_requests as $schedule_ride_request) {
            $this->findNearbyDriver($schedule_ride_request, $radius, $min_amount);
        }
    }

    protected function findNearbyDriver($schedule_ride_request, $radius, $min_amount)
    {
        $unit = $schedule_ride_request->distance_unit ?? 'km';
        $unit_value = convertUnitvalue($unit);
        $latitude = $schedule_ride_request->start_latitude;
        $longitude = $schedule_ride_request->start_longitude;

        // Log::info("Ride Request - Lat: $latitude, Long: $longitude, Unit: $unit_value");

        $cancelled_driver_ids = $schedule_ride_request->cancelled_driver_ids ?? [];

        $rejected_bid_driver_ids = is_string($schedule_ride_request->rejected_bid_driver_ids) ? json_decode($schedule_ride_request->rejected_bid_driver_ids, true) : ($schedule_ride_request->rejected_bid_driver_ids ?? []);
        $rejected_bid_driver_ids = is_array($rejected_bid_driver_ids) ? $rejected_bid_driver_ids : [];

        $nearby_driver = User::selectRaw("id, user_type, player_id, latitude, longitude, ( $unit_value * acos( cos( radians($latitude) ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians($longitude) ) + sin( radians($latitude) ) * sin( radians( latitude ) ) ) ) AS distance")
            ->where('user_type', 'driver')
            ->where('status', 'active')
            ->where('is_online', 1)
            ->where('is_available', 1)
            ->where('service_id', $schedule_ride_request->service_id)
            ->whereNotIn('id', $cancelled_driver_ids)
            ->whereNotIn('id', $rejected_bid_driver_ids)
            ->having('distance', '<=', $radius)
            ->when($min_amount, function ($query) use ($min_amount) {
                $query->whereHas('userWallet', function ($q) use ($min_amount) {
                    $q->where('total_amount', '>=', $min_amount);
                });
            })
            ->orderBy('distance', 'asc')
            ->first();

        //dd($nearby_driver);
        if ($nearby_driver) {
            // Log::info('Nearby driver found: Driver ID: ' . $nearby_driver->id . ', Distance: ' . $nearby_driver->distance);
            $this->assignDriver($schedule_ride_request, $nearby_driver,$radius,$min_amount);
        } else {
            $schedule_ride_request->increment('ride_attempt');
            // Log::info('No driver found for ride request ID: ' . $schedule_ride_request->id . '. Ride attempt incremented to ' . $schedule_ride_request->ride_attempt);

            if ($schedule_ride_request->ride_attempt > 5) {
                $schedule_ride_request->update([
                    'status' => 'canceled',
                    'cancelled_by' => 'auto',
                ]);
                // Log::info('Ride request ID: ' . $schedule_ride_request->id . ' has been auto-canceled after 5 attempts.');
            }
        }
    }

    protected function assignDriver($schedule_ride_request, $driver, $radius, $min_amount)
    {
        // Ensure we don't assign the same driver again
        if ($schedule_ride_request->riderequest_in_driver_id != $driver->id) {
            $schedule_ride_request->update([
                'riderequest_in_driver_id' => $driver->id,
                'riderequest_in_datetime' => Carbon::now()->format('Y-m-d H:i:s'),
                //'status' => 'new_ride_requested',
            ]);

            // Log::info('Driver ID: ' . $driver->id . ' assigned to Ride Request ID: ' . $schedule_ride_request->id);

            // Send notification to the assigned driver
            $notification_data = [
                'id' => $schedule_ride_request->id,
                'type' => 'scheduled',
                'data' => [
                    'rider_id' => $schedule_ride_request->rider_id,
                    'rider_name' => optional($schedule_ride_request->rider)->display_name ?? '',
                ],
                'message' => __('message.scheduled'),
                'subject' => __('message.ride.scheduled'),
            ];

            try {
                $driver->notify(new CommonNotification($notification_data['type'], $notification_data));
                // Log::info('Notification sent to Driver ID: ' . $driver->id);
            } catch (\Exception $e) {
                Log::error('Error sending notification to Driver ID: ' . $driver->id . '. ' . $e->getMessage());
            }

            $this->startResponseTimer($schedule_ride_request, $driver, $radius, $min_amount);
            
            // Log::info('Updating Firebase with Driver ID: ' . $driver->id . ' for Ride Request ID: ' . $schedule_ride_request->id);
        } else {
            // Log::info('Driver ID: ' . $driver->id . ' is already assigned to Ride Request ID: ' . $schedule_ride_request->id . ', skipping reassignment.');
        }
    }

    protected function startResponseTimer($schedule_ride_request, $driver, $radius, $min_amount)
    {
        $response_time = SettingData('ride','ride_accept_decline_duration_for_driver_in_second') ?? null;
        sleep($response_time);

        $schedule_ride_request->refresh();

        if ($schedule_ride_request->status === 'scheduled' && $schedule_ride_request->riderequest_in_driver_id === $driver->id) {
            $schedule_ride_request->increment('ride_attempt');
            $this->findNearbyDriver($schedule_ride_request, $radius, $min_amount); // Pass radius and min_amount
        }
    }
}
