<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\RideRequest;
use App\Models\User;
use App\Models\Setting;
use App\Notifications\CommonNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Traits\RideRequestTrait;
use App\Models\RideRequestHistory;

class ProcessScheduledRides extends Command
{
    use RideRequestTrait;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scheduleride:process-schedule-rides';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process schedule ride requests';

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
        try {
            Log::channel('process_schedule_rides')->info("Cron started: Processing scheduled rides [Line: " . __LINE__ . "]");

            $driver_accept_time = SettingData('ride','ride_accept_decline_duration_for_driver_in_second') ?? 0;

            // Get scheduled rides that are due within the next 15 minutes
            $scheduledRides = RideRequest::where('is_schedule', 1)->whereIn('status', ['scheduled','driver_accepted'])->where('scheduled_at', '<=', Carbon::now()->addMinutes(15))->where('scheduled_at', '>=', Carbon::now())->get();

            Log::channel('process_schedule_rides')->info("Found {$scheduledRides->count()} rides scheduled within next 15 minutes [Line: " . __LINE__ . "]");

            foreach ($scheduledRides as $ride) {
                Log::channel('process_schedule_rides')->info("Processing ride ID: {$ride->id}, Driver ID: " . ($ride->driver_id ?? 'N/A') . " [Line: " . __LINE__ . "]");

                // Check if driver is assigned
                if ($ride->driver_id) {
                    $this->processRideWithDriver($ride);
                } else {
                    Log::channel('process_schedule_rides')->info("No driver assigned for ride #{$ride->id}. Using fallback mechanism [Line: " . __LINE__ . "]");
                    
                    $previousDriver = [];
                    if(!empty($ride->riderequest_in_driver_id)){
                        // Check if enough time has passed since assignment
                        $driverAssignedAt = $ride->riderequest_in_datetime ?? $ride->created_at; // fallback
                        $elapsedSinceAssignment = Carbon::parse($driverAssignedAt)->diffInSeconds(now());

                        if ($elapsedSinceAssignment >= $driver_accept_time) {
                            // Driver’s response time expired → mark declined
                            $previousDriver = [
                                'id'        => $ride->id,
                                'driver_id' => $ride->riderequest_in_driver_id,
                                'is_accept' => "0"
                            ];
                        }
                    }

                    // No driver assigned - use fallback mechanism
                    $this->acceptDeclinedRide($ride, $previousDriver);
                }
            }

            // Cancel overdue rides without drivers
            $this->cancelOverdueRides();
            Log::channel('process_schedule_rides')->info("Cron completed successfully [Line: " . __LINE__ . "]");
            return 0;
        } catch (\Exception $e) {
            Log::channel('process_schedule_rides')->error("Error processing scheduled rides: {$e->getMessage()} [Line: " . __LINE__ . "]");
            return 1;
        }
    }

    private function processRideWithDriver($ride)
    {
        $driver = User::find($ride->driver_id);
        if (!$driver) {
            Log::channel('process_schedule_rides')->warning("Driver not found for ride #{$ride->id} [Line: " . __LINE__ . "]");
            return;
        }

        Log::channel('process_schedule_rides')->info("Processing ride #{$ride->id} with driver #{$driver->id} [Line: " . __LINE__ . "]");

        // Add entry to Firestore
        $document_name = 'ride_' . $ride->id;
        $firebaseData = app('firebase.firestore')->database()->collection('rides')->document($document_name);
        if ($firebaseData) {
            $rideData = [
                'driver_ids' => [$ride->driver_id],
                'on_rider_stream_api_call' => 1,
                'on_stream_api_call' => 0,
                'ride_id' => $ride->id,
                'rider_id' => $ride->rider_id,
                'status' => 'accepted',
                'payment_status' => '',
                'payment_type' => '',
                'tips' => 0,
            ];
            $firebaseData->set($rideData);
            Log::channel('process_schedule_rides')->info("Ride #{$ride->id} added/updated in Firebase successfully [Line: " . __LINE__ . "]");
        }

        // Send notification to driver
        $notification_data = [
            'id' => $ride->id,
            'type' => 'scheduled_ride_reminder',
            'data' => [
                'rider_id' => $ride->rider_id,
                'rider_name' => optional($ride->rider)->display_name ?? '',
            ],
            'message' => 'You have a scheduled ride coming up in ' . Carbon::now()->diffInMinutes($ride->scheduled_at) . ' minutes.',
            'subject' => 'Upcoming Scheduled Ride',
        ];
        
        $driver->notify(new CommonNotification($notification_data['type'], $notification_data));
        $ride->driver->update(['is_available' => 0]);
        $ride->update(['status' => 'accepted']);

        Log::channel('process_schedule_rides')->info("Scheduled ride notification sent to driver #{$driver->id} for ride #{$ride->id} [Line: " . __LINE__ . "]");

        // Store ride history
        $data = [
            'datetime' => Carbon::now()->format('Y-m-d H:i:s'),
            'history_type' => 'accepted',
            'history_message' => __('message.ride.accepted'),
            'ride_request_id' => $ride->id,
            'history_data' => json_encode([
                'driver_id' => $ride->driver_id,
                'driver_name' => optional($ride->driver)->display_name ?? '',
            ])
        ];
        RideRequestHistory::create($data);
        Log::channel('process_schedule_rides')->info("Ride history created for ride #{$ride->id} [Line: " . __LINE__ . "]");

        // Send notification to rider
        if ($rider = User::find($ride->rider_id)) {
            $rider_notification_data = [
                'id' => $ride->id,
                'type' => 'scheduled_ride_reminder',
                'data' => ['driver_name' => optional($ride->driver)->display_name],
                'message' => 'You have a scheduled ride coming up in ' . Carbon::now()->diffInMinutes($ride->scheduled_at) . ' minutes.',
                'subject' => 'Upcoming Scheduled Ride',
            ];
            $rider->notify(new CommonNotification($rider_notification_data['type'], $rider_notification_data));

            Log::channel('process_schedule_rides')->info("Scheduled ride reminder sent to rider #{$rider->id} for ride #{$ride->id} [Line: " . __LINE__ . "]");
        }
        
        Log::info('Scheduled ride notification sent to driver #' . $driver->id . ' for ride #' . $ride->id);
    }

    private function cancelOverdueRides()
    {
        // Cancel rides that are overdue and have no driver assigned
        $overdueRides = RideRequest::where('is_schedule', 1)
            ->whereIn('status', ['scheduled', 'driver_accepted'])
            ->where('scheduled_at', '<', Carbon::now())
            ->get();

        Log::channel('process_schedule_rides')->info("Found {$overdueRides->count()} overdue scheduled rides to cancel [Line: " . __LINE__ . "]");

        foreach ($overdueRides as $ride) {
            $ride->update([
                'status' => 'canceled',
                'cancel_by' => 'auto',
                'reason' => 'Automatically canceled - no driver assigned and scheduled time passed'
            ]);

            // Add history entry
            RideRequestHistory::create([
                'datetime' => Carbon::now()->format('Y-m-d H:i:s'),
                'history_type' => 'canceled',
                'history_message' => 'Ride automatically canceled - no driver assigned and scheduled time passed',
                'ride_request_id' => $ride->id,
                'history_data' => json_encode([
                    'canceled_by' => 'system',
                    'reason' => 'No driver assigned and scheduled time passed'
                ])
            ]);

            // Notify rider
            if ($rider = User::find($ride->rider_id)) {
                $notificationData = [
                    'id' => $ride->id,
                    'type' => 'ride_canceled',
                    'data' => ['ride_id' => $ride->id, 'canceled_by' => 'system'],
                    'message' => 'Your scheduled ride has been automatically canceled as no driver was available.',
                    'subject' => 'Scheduled Ride Canceled',
                ];
                $rider->notify(new CommonNotification($notificationData['type'], $notificationData));

                Log::channel('process_schedule_rides')->info("Notification sent to rider #{$rider->id} for canceled ride #{$ride->id} [Line: " . __LINE__ . "]");
            }

            Log::channel('process_schedule_rides')->info("Automatically canceled overdue scheduled ride #{$ride->id} [Line: " . __LINE__ . "]");
        }
    }

    protected function acceptDeclinedRide($ride_request,$request_data = null)
    {
        $unit = $ride_request->distance_unit ?? 'km';
        $unit_value = convertUnitvalue($unit);
        $radius = Setting::where('type','DISTANCE')->where('key','DISTANCE_RADIUS')->pluck('value')->first() ?? 50;
                    
        $latitude = $ride_request->start_latitude;
        $longitude = $ride_request->start_longitude;

        $cancelled_driver_ids = $ride_request->cancelled_driver_ids ?: [];
        
        if ($request_data != null && $request_data['is_accept'] == 0) {
            array_push($cancelled_driver_ids, $request_data['driver_id']);
            Log::channel('process_schedule_rides')->info("Driver ID {$request_data['driver_id']} added to cancelled_driver_ids for Ride ID: {$ride_request->id} [Line: " . __LINE__ . "]");
        }

        Log::channel('process_schedule_rides')->info("Ride ID: {$ride_request->id} cancelled_driver_ids: " . json_encode($cancelled_driver_ids) . " [Line: " . __LINE__ . "]");

        $minumum_amount_get_ride = SettingData('wallet', 'min_amount_to_get_ride') ?? null;

        $limitTime = now()->subMinutes(30);

        $nearby_driver = User::selectRaw("id, user_type, player_id, latitude, longitude, ( $unit_value * acos( cos( radians($latitude) ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians($longitude) ) + sin( radians($latitude) ) * sin( radians( latitude ) ) ) ) AS distance")
                        ->where('user_type', 'driver')->where('status', 'active')->where('is_online',1)->where('is_available',1)
                        ->where('service_id', $ride_request->service_id )
                        ->whereNotIn('id', $cancelled_driver_ids)
                        ->where('last_actived_at', '>=', $limitTime) // NEW CONDITION
                        ->having('distance', '<=', $radius)
                        ->orderBy('distance','asc');
        if( $minumum_amount_get_ride != null ) {
            $nearby_driver = $nearby_driver->whereHas('userWallet', function($q) use($minumum_amount_get_ride) {
                $q->where('total_amount', '>=', $minumum_amount_get_ride);
            });
        }
        $nearby_driver = $nearby_driver->first();

        if( $nearby_driver != null )
        {
            Log::channel('process_schedule_rides')->info("Nearby driver found (Driver ID: {$nearby_driver->id}) for Ride ID: {$ride_request->id} [Line: " . __LINE__ . "]");

            $data['riderequest_in_driver_id'] = $nearby_driver->id;
            $data['riderequest_in_datetime'] = Carbon::now()->format('Y-m-d H:i:s');
            
        } else {
            Log::channel('process_schedule_rides')->warning("No nearby driver found for Ride ID: {$ride_request->id} [Line: " . __LINE__ . "]");
            $data['riderequest_in_driver_id'] = null;
            $data['riderequest_in_datetime'] = null;
        }

        $data['cancelled_driver_ids'] = $cancelled_driver_ids;
        $data['ride_attempt'] = $ride_request->ride_attempt + 1;
        $data['status'] = 'new_ride_requested';
        $ride_request->fill($data)->update();
        
        try {
            // Add history entry
            RideRequestHistory::create([
                'datetime' => Carbon::now()->format('Y-m-d H:i:s'),
                'history_type' => 'new_ride_requested',
                'history_message' => __('message.ride.new_ride_requested'),
                'ride_request_id' => $ride_request->id,
                'history_data' => json_encode([
                    'rider_id' => $ride_request->rider_id,
                    'rider_name' => optional($ride_request->rider)->display_name ?? ''
                ])
            ]);
        } catch (\Exception $e) {
            Log::channel('process_schedule_rides')->error("Error while creating ride history Ride ID: {$ride_request->id} → " . $e->getMessage() . " [Line: " . __LINE__ . "]");
        }
        

        try {
            $document_name = 'ride_' . $ride_request->id;
            $firebaseData = app('firebase.firestore')->database()->collection('rides')->document($document_name);

            if ($firebaseData) {
                $rideData = [
                    'driver_ids' => [$data['riderequest_in_driver_id']] ?? [$ride_request->riderequest_in_driver_id],
                    'on_rider_stream_api_call' => 1,
                    'on_stream_api_call' => 1,
                    'ride_id' => $ride_request->id,
                    'rider_id' => $ride_request->rider_id,
                    'status' => $ride_request->status,
                    'payment_status' => '',
                    'payment_type' => '',
                    'tips' => 0,
                ];

                $firebaseData->set($rideData);
                Log::channel('process_schedule_rides')->info("Firebase updated for Ride ID: {$ride_request->id} [Line: " . __LINE__ . "]");

                if ($nearby_driver) {
                    $nearby_driver->notify(new CommonNotification('new_ride_requested', [
                        'id' => $ride_request->id,
                        'type' => 'new_ride_requested',
                        'data' => [
                            'rider_id' => $ride_request->rider_id,
                            'rider_name' => optional($ride_request->rider)->display_name ?? '',
                        ],
                        'message' => __('message.new_ride_requested'),
                        'subject' => __('message.ride.new_ride_requested'),
                    ]));
                    Log::channel('process_schedule_rides')->info("Driver (ID: {$nearby_driver->id}) notified for Ride ID: {$ride_request->id} [Line: " . __LINE__ . "]");
                }
            } else {
                Log::channel('process_schedule_rides')->error("Firebase document missing for Ride ID: {$ride_request->id} [Line: " . __LINE__ . "]");
            }
        } catch (\Exception $e) {
            Log::channel('process_schedule_rides')->error("Error updating Firebase for Ride ID: {$ride_request->id} → " . $e->getMessage() . " [Line: " . __LINE__ . "]");
        }
        return $ride_request;
    }
}
