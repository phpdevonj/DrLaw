<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\RideRequest;
use App\Models\User;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Notifications\CommonNotification;
use App\Jobs\NotifyViaMqtt;
use App\Http\Resources\RideRequestResource;
use App\Models\RideRequestHistory;
use Illuminate\Support\Facades\Log;

class FindDriverForRegularRide extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'find_driver:for_regular_ride';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Find Drivers for Regular Ride';

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
        $timeoutMinutes = SettingData('ride', 'max_time_for_find_drivers_for_regular_ride_in_minute') ?? 2;
        $driver_accept_time = SettingData('ride','ride_accept_decline_duration_for_driver_in_second') ?? 0;
        $maxAttempts = 5;

        $rides = RideRequest::where('status', 'new_ride_requested')
            ->where('is_schedule', 0)
            ->whereNotIn('status', ['canceled', 'completed'])
            ->get();

        Log::channel('driver_assignment_regular')->info("Cron started: processing {$rides->count()} rides [Line: " . __LINE__ . "]");

        foreach ($rides as $ride) {
            $elapsedMinutes = Carbon::parse($ride->created_at)->diffInMinutes(Carbon::now());
            $attempts = $ride->ride_attempt ?? 0;
            
            Log::channel('driver_assignment_regular')->info(
                "Checking Ride ID: {$ride->id} | Attempts: {$attempts} | Elapsed: {$elapsedMinutes} mins [Line: " . __LINE__ . "]"
            );

            // Expire condition
            if ($elapsedMinutes >= $timeoutMinutes || $attempts >= $maxAttempts) {
                $this->autoCancelRide($ride);
                continue;
            }

            //
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
            
                    Log::channel('driver_assignment_regular')->info(
                        "Ride ID: {$ride->id} → Driver ID {$ride->riderequest_in_driver_id} marked declined (elapsed {$elapsedSinceAssignment}s) [Line: " . __LINE__ . "]"
                    );
                } else {
                    Log::channel('driver_assignment_regular')->info(
                        "Ride ID: {$ride->id} → Driver ID {$ride->riderequest_in_driver_id} still within acceptance window ({$elapsedSinceAssignment}s), skipping decline. [Line: " . __LINE__ . "]"
                    );
                }
            }

            // Run driver search only if driver declined OR no driver was ever assigned
            if (!empty($previousDriver) || empty($ride->riderequest_in_driver_id)) {
                $ride->increment('ride_attempt');
                $attempts = $ride->ride_attempt;

                Log::channel('driver_assignment_regular')->info(
                    "Ride ID: {$ride->id} attempt incremented to {$attempts} [Line: " . __LINE__ . "]"
                );

                $this->acceptDeclinedRide($ride, $previousDriver);

                Log::channel('driver_assignment_regular')->info(
                    "acceptDeclinedRide() executed for Ride ID: {$ride->id} [Line: " . __LINE__ . "]"
                );
            } else {
                Log::channel('driver_assignment_regular')->info(
                    "Ride ID: {$ride->id} waiting for Driver ID {$ride->riderequest_in_driver_id} response, skipping new search. [Line: " . __LINE__ . "]"
                );
            }
        }
        Log::channel('driver_assignment_regular')->info("Cron finished [Line: " . __LINE__ . "]");
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
            Log::channel('driver_assignment_regular')->info("Driver ID {$request_data['driver_id']} added to cancelled_driver_ids for Ride ID: {$ride_request->id} [Line: " . __LINE__ . "]");
        }

        Log::channel('driver_assignment_regular')->info("Ride ID: {$ride_request->id} cancelled_driver_ids: " . json_encode($cancelled_driver_ids) . " [Line: " . __LINE__ . "]");

        $minumum_amount_get_ride = SettingData('wallet', 'min_amount_to_get_ride') ?? null;

        $nearby_driver = User::selectRaw("id, user_type, player_id, latitude, longitude, ( $unit_value * acos( cos( radians($latitude) ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians($longitude) ) + sin( radians($latitude) ) * sin( radians( latitude ) ) ) ) AS distance")
                        ->where('user_type', 'driver')->where('status', 'active')->where('is_online',1)->where('is_available',1)
                        ->where('service_id', $ride_request->service_id )
                        ->whereNotIn('id', $cancelled_driver_ids)
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
            Log::channel('driver_assignment_regular')->info("Nearby driver found (Driver ID: {$nearby_driver->id}) for Ride ID: {$ride_request->id} [Line: " . __LINE__ . "]");

            $data['riderequest_in_driver_id'] = $nearby_driver->id;
            $data['riderequest_in_datetime'] = Carbon::now()->format('Y-m-d H:i:s');
            
        } else {
            Log::channel('driver_assignment_regular')->warning("No nearby driver found for Ride ID: {$ride_request->id} [Line: " . __LINE__ . "]");
            $data['riderequest_in_driver_id'] = null;
            $data['riderequest_in_datetime'] = null;
        }

        $data['cancelled_driver_ids'] = $cancelled_driver_ids;
        $ride_request->fill($data)->update();
        

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
                Log::channel('driver_assignment_regular')->info("Firebase updated for Ride ID: {$ride_request->id} [Line: " . __LINE__ . "]");

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
                    Log::channel('driver_assignment_regular')->info("Driver (ID: {$nearby_driver->id}) notified for Ride ID: {$ride_request->id} [Line: " . __LINE__ . "]");
                }
            } else {
                Log::channel('driver_assignment_regular')->error("Firebase document missing for Ride ID: {$ride_request->id} [Line: " . __LINE__ . "]");
            }
        } catch (\Exception $e) {
            Log::channel('driver_assignment_regular')->error("Error updating Firebase for Ride ID: {$ride_request->id} → " . $e->getMessage() . " [Line: " . __LINE__ . "]");
        }
        return $ride_request;
    }


    protected function autoCancelRide($ride)
    {
        if ($ride->status !== 'new_ride_requested') {
            return; // Already canceled or accepted
        }

        $ride->update([
            'status' => 'canceled',
            'cancel_by' => 'auto',
            'reason' => 'Automatically canceled – no driver found within the search time limit'
        ]);

        // Add entry to ride history
        try {
            RideRequestHistory::create([
                'datetime' => Carbon::now()->format('Y-m-d H:i:s'),
                'history_type' => 'canceled',
                'history_message' => 'Ride Automatically canceled – no driver found within the search time limit',
                'ride_request_id' => $ride->id,
                'history_data' => json_encode([
                    'canceled_by' => 'system',
                    'reason' => 'No driver assigned and searching time passed'
                ])
            ]);
            Log::channel('driver_assignment_regular')->info("History entry added for canceled Ride ID: {$ride->id} [Line: " . __LINE__ . "]");
        } catch (\Exception $e) {
            Log::channel('driver_assignment_regular')->error("Error saving history for Ride ID: {$ride->id} → " . $e->getMessage() . " [Line: " . __LINE__ . "]");
        }

        // Send notification to rider
        if ($rider = User::find($ride->rider_id)) {
            $notificationData = [
                'id' => $ride->id,
                'type' => 'ride_canceled',
                'data' => ['ride_id' => $ride->id, 'canceled_by' => 'system'],
                'message' => 'Your ride was automatically canceled as no drivers were available.',
                'subject' => 'Ride Canceled',
            ];

            try {
                $rider->notify(new CommonNotification($notificationData['type'], $notificationData));
                Log::channel('driver_assignment_regular')->info("Rider (User ID: {$rider->id}) notified of auto-cancellation for Ride ID: {$ride->id} [Line: " . __LINE__ . "]");
            } catch (\Exception $e) {
                Log::channel('driver_assignment_regular')->error("Error sending notification to Rider ID: {$rider->id} for Ride ID: {$ride->id} → " . $e->getMessage() . " [Line: " . __LINE__ . "]");
            }
        }

        // Delete from Firebase
        $this->deleteRideFromFirebase($ride->id);

        Log::channel('driver_assignment_regular')->warning("Ride ID {$ride->id} auto-canceled. [Line: " . __LINE__ . "]");
    }


    protected function deleteRideFromFirebase($rideId)
    {
        try {
            $document_name = 'ride_' . $rideId;
            $firestore = app('firebase.firestore')->database();
            $documentRef = $firestore->collection('rides')->document($document_name);
            if($documentRef->snapshot()->exists()) {
                $documentRef->delete();
            }            
            Log::channel('driver_assignment_regular')->info('Ride deleted from Firebase successfully for Ride ID: ' . $rideId . ' [Line: ' . __LINE__ . ']');
        } catch (\Exception $e) {
            Log::channel('driver_assignment_regular')->error('Error deleting Ride from Firebase for Ride ID: ' . $rideId . ': ' . $e->getMessage() . ' [Line: ' . __LINE__ . ']');
        }
    }
}