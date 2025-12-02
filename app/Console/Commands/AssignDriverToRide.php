<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\RideRequest;
use App\Models\User;
use App\Models\Setting;
use App\Notifications\CommonNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Models\RideRequestHistory;

class AssignDriverToRide extends Command
{
    protected $signature = 'ride:assign-drivers-for-regular-rides';
    protected $description = 'Assign drivers to regular ride requests';

    public function handle()
    {
        Log::channel('driver_assignment_regular')->info('==== Driver assignment process started ==== [Line: ' . __LINE__ . ']');

        $radius = Setting::where('type', 'DISTANCE')->where('key', 'DISTANCE_RADIUS')->value('value') ?? 50; // Default radius
        $min_amount = Setting::where('key', 'min_amount_to_get_ride')->value('value') ?? null;

        $max_search_time = SettingData('ride', 'max_time_for_find_drivers_for_regular_ride_in_minute') ?? 2;
        $max_search_time = $max_search_time * 60; // convert to seconds

        $driver_accept_time = SettingData('ride','ride_accept_decline_duration_for_driver_in_second') ?? 15; // 15 seconds per driver

        Log::channel('driver_assignment_regular')->info("Settings loaded → Radius: {$radius}, Min Wallet: {$min_amount}, Max Search Time: {$max_search_time}s, Driver Accept Time: {$driver_accept_time}s [Line: " . __LINE__ . "]");

        $ride_requests = RideRequest::where('is_schedule', 0)
            ->where('status', 'new_ride_requested')
            //->where('created_at', '>=', Carbon::now()->subMinutes(5))
            //->where('ride_attempt', '<', 5) // strictly less than 5
            ->get();

        Log::channel('driver_assignment_regular')->info('Total ride requests fetched: ' . $ride_requests->count() . ' [Line: ' . __LINE__ . ']');

        //foreach ($ride_requests as $ride_request) {
            //$this->findNearbyDriver($ride_request, $radius, $min_amount);
        //}

        foreach ($ride_requests as $ride) {
            Log::channel('driver_assignment_regular')->info("Processing Ride ID: {$ride->id} (Attempts: {$ride->ride_attempt}) [Line: " . __LINE__ . "]");
            //Log::channel('driver_assignment_regular')->info("---- Processing Ride ID: {$ride->id} (Rider: {$ride->rider_id}, Attempts: {$ride->ride_attempt}) ---- [Line: " . __LINE__ . "]");
            //$start_time = time();
    
            //while ((time() - $start_time) < $max_search_time && $ride->ride_attempt < 5) {
                //Log::channel('driver_assignment_regular')->info("Searching driver for Ride ID: {$ride->id}, elapsed=" . (time() - $start_time) . "s [Line: " . __LINE__ . "]");

                // Check if ride search expired
                $elapsed = Carbon::now()->diffInSeconds($ride->created_at);
                if ($elapsed >= $max_search_time || $ride->ride_attempt >= 5) {
                    $this->autoCancelRide($ride);
                    continue;
                }
    
                // Find nearest available driver excluding rejected & cancelled
                try {
                    $driver = $this->findNextAvailableDriver($ride, $radius, $min_amount);
                } catch (\Exception $e) {
                    Log::channel('driver_assignment_regular')->error("Error finding driver for Ride ID: {$ride->id} → " . $e->getMessage() . " [Line: " . __LINE__ . "]", ['trace' => $e->getTraceAsString()]);
                    break;
                }

                // if (!$driver) {
                //     // No driver currently available, retry immediately
                //     Log::channel('driver_assignment_regular')->info("No driver found for Ride ID: {$ride->id}, retrying in 0.5s... [Line: " . __LINE__ . "]");
                //     usleep(500000); // optional 0.5 second pause to avoid DB overload
                //     continue;
                // }

                //Log::channel('driver_assignment_regular')->info("Driver found (Driver ID: {$driver->id}) for Ride ID: {$ride->id}, assigning... [Line: " . __LINE__ . "]");


                if ($driver) {
                    Log::channel('driver_assignment_regular')->info("Assigning Driver ID {$driver->id} to Ride ID {$ride->id} [Line: " . __LINE__ . "]");

                    // Assign ride to this driver and notify
                    try {
                        $this->assignDriver($ride, $driver);
                    } catch (\Exception $e) {
                        Log::channel('driver_assignment_regular')->error("Error assigning Driver ID: {$driver->id} to Ride ID: {$ride->id} → " . $e->getMessage() . " [Line: " . __LINE__ . "]", ['trace' => $e->getTraceAsString()]);
                        break;
                    }
                } else {
                    // No driver found in this run → increment attempt
                    $ride->increment('ride_attempt');
                    Log::channel('driver_assignment_regular')->info("No driver found, Ride ID {$ride->id} incremented attempt to {$ride->ride_attempt} [Line: " . __LINE__ . "]");
                }
    
                
    
                // Wait for driver's response
                //Log::channel('driver_assignment_regular')->info("Waiting {$driver_accept_time}s for Driver ID: {$driver->id} response (Ride ID: {$ride->id}) [Line: " . __LINE__ . "]");
                //sleep($driver_accept_time);
    
                //$ride->refresh();
    
                //if ($ride->status === 'accepted') {
                    //Log::channel('driver_assignment_regular')->info("Ride ID: {$ride->id} accepted by Driver ID: {$ride->driver_id} [Line: " . __LINE__ . "]");
                    //break; // Ride accepted, stop search
                //}

                //Log::channel('driver_assignment_regular')->info("Driver ID: {$driver->id} did not accept Ride ID: {$ride->id}, marking as rejected. [Line: " . __LINE__ . "]");
    
                // Mark this driver as rejected for this ride
                //$cancelled_driver_ids = $ride->cancelled_driver_ids ?? [];
                //if (!is_array($cancelled_driver_ids)) {
                    //$cancelled_driver_ids = is_string($cancelled_driver_ids) ? json_decode($cancelled_driver_ids, true) : [];
                //}
                //$cancelled_driver_ids[] = $driver->id;
                //$ride->update(['cancelled_driver_ids' => $cancelled_driver_ids]);

                //$ride->increment('ride_attempt');
                //Log::channel('driver_assignment_regular')->info("Ride ID: {$ride->id} incremented ride_attempt to {$ride->ride_attempt} [Line: " . __LINE__ . "]");
            //}
        }
        Log::channel('driver_assignment_regular')->info('==== Driver assignment process completed ==== [Line: ' . __LINE__ . ']');
    }

    /**
     * Find next nearest available driver for a ride
     */
    protected function findNextAvailableDriver($ride, $radius, $min_amount)
    {
        $unit = $ride->distance_unit ?? 'km';
        $unit_value = convertUnitvalue($unit);
        $latitude = $ride->start_latitude;
        $longitude = $ride->start_longitude;

        $cancelled_driver_ids = $ride->cancelled_driver_ids ?? [];
        $rejected_driver_ids = $ride->rejected_bid_driver_ids ?? [];
        $rejected_driver_ids = is_string($rejected_driver_ids) ? json_decode($rejected_driver_ids, true) : $rejected_driver_ids;

        $driver = User::selectRaw("id, user_type, player_id, latitude, longitude, ( $unit_value * acos( cos( radians($latitude) ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians($longitude) ) + sin( radians($latitude) ) * sin( radians( latitude ) ) ) ) AS distance")
            ->where('user_type', 'driver')
            ->where('status', 'active')
            ->where('is_online', 1)
            ->where('is_available', 1)
            ->where('service_id', $ride->service_id)
            ->whereNotIn('id', $cancelled_driver_ids)
            ->whereNotIn('id', $rejected_driver_ids)
            ->having('distance', '<=', $radius)
            ->when($min_amount, function ($query) use ($min_amount) {
                $query->whereHas('userWallet', function ($q) use ($min_amount) {
                    $q->where('total_amount', '>=', $min_amount);
                });
            })
            ->orderBy('distance', 'asc')
            ->first();

        return $driver;
    }

    /**
     * Assign ride to a driver and notify
     */
    protected function assignDriver($ride, $driver)
    {
        $ride->update([
            'riderequest_in_driver_id' => $driver->id,
            'riderequest_in_datetime' => Carbon::now(),
            'status' => 'new_ride_requested',
        ]);
    
        // Send notification
        $notification_data = [
            'id' => $ride->id,
            'type' => 'new_ride_requested',
            'data' => [
                'rider_id' => $ride->rider_id,
                'rider_name' => optional($ride->rider)->display_name ?? '',
            ],
            'message' => __('message.new_ride_requested'),
            'subject' => __('message.ride.new_ride_requested'),
        ];
    
        try {
            $driver->notify(new CommonNotification($notification_data['type'], $notification_data));
        } catch (\Exception $e) {
            Log::channel('driver_assignment_regular')->error('Error sending notification to Driver ID: ' . $driver->id . '. ' . $e->getMessage() . ' [Line: ' . __LINE__ . ']');
        }
    
        // Update Firebase
        $this->updateFirebaseWithDriver($ride, $driver);
    }

    protected function startResponseTimer($ride_request, $driver, $radius, $min_amount)
    {
        $response_time = SettingData('ride','ride_accept_decline_duration_for_driver_in_second') ?? null;
        sleep($response_time);

        $ride_request->refresh();

        if ($ride_request->status === 'new_ride_requested' && $ride_request->riderequest_in_driver_id === $driver->id) {
            $ride_request->increment('ride_attempt');
            $this->findNearbyDriver($ride_request, $radius, $min_amount); // Pass radius and min_amount
        }
    }

    protected function updateFirebaseWithDriver($ride_request, $driver)
    {
        try {
            $document_name = 'ride_' . $ride_request->id;
            $firestore = app('firebase.firestore')->database();
            $documentRef = $firestore->collection('rides')->document($document_name);

            $nearby_driver_ids = $ride_request->nearby_driver_ids;
            
            if (is_string($nearby_driver_ids)) {
                $nearby_driver_ids = json_decode($nearby_driver_ids, true);
            } elseif (is_object($nearby_driver_ids)) {
                $nearby_driver_ids = (array)$nearby_driver_ids;
            }

            $nearby_driver_ids = is_array($nearby_driver_ids) ? $nearby_driver_ids : [];

            $rejected_bid_driver_ids = is_string($ride_request->rejected_bid_driver_ids) 
                ? json_decode($ride_request->rejected_bid_driver_ids, true) : [];
            $rejected_bid_driver_ids = array_filter($rejected_bid_driver_ids);

            $updated_nearby_driver_ids = !empty($nearby_driver_ids) && !empty($rejected_bid_driver_ids) 
                ? array_diff($nearby_driver_ids, $rejected_bid_driver_ids)
                : $nearby_driver_ids;

            $updated_nearby_driver_ids = is_array($updated_nearby_driver_ids) ? $updated_nearby_driver_ids : [];

            $rideData = [
                'on_rider_stream_api_call' => 1,
                'on_stream_api_call' => 0,
                'ride_id' => $ride_request->id,
                'rider_id' => $ride_request->rider_id,
                'status' => $ride_request->status,
                'driver_ids' => array_values($updated_nearby_driver_ids),
            ];

            $rideRequestStatuses = ['bid_accepted', 'arrived', 'in_progress', 'completed', 'accepted', 'arriving'];

            if (in_array($ride_request->status, $rideRequestStatuses)) {
                $rideData['driver_ids'] = [$ride_request->driver_id];
            }

            $documentRef->set($rideData, ['merge' => true]);

            Log::channel('driver_assignment_regular')->info('Firebase updated successfully for Ride Request ID: ' . $ride_request->id . ' with Driver ID: ' . $driver->id . ' [Line: ' . __LINE__ . ']');

        } catch (\Exception $e) {
            Log::channel('driver_assignment_regular')->error('Error updating Firebase for Ride Request ID: ' . $ride_request->id . ': ' . $e->getMessage() . ' [Line: ' . __LINE__ . ']');
        }
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
