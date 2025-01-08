<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\RideRequest;
use App\Models\User;
use App\Models\Setting;
use App\Notifications\CommonNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AssignDriverToRide extends Command
{
    protected $signature = 'ride:assign-drivers-for-regular-rides';
    protected $description = 'Assign drivers to regular ride requests';

    public function handle()
    {
        // Log::info('Starting driver assignment process.');

        $radius = Setting::where('type', 'DISTANCE')->where('key', 'DISTANCE_RADIUS')->value('value') ?? 50; // Default radius
        $min_amount = Setting::where('key', 'min_amount_to_get_ride')->value('value') ?? null;

        // Log::info("Radius: $radius, Min amount: $min_amount");

        $ride_requests = RideRequest::where('is_schedule', 0)
            ->where('status', 'new_ride_requested')
            ->where('created_at', '>=', Carbon::now()->subMinutes(5))
            ->where('ride_attempt', '<=', 5)
            ->get();

        // Log::info('Total ride requests found: ' . $ride_requests->count());

        foreach ($ride_requests as $ride_request) {
            // Log::info('Processing ride request ID: ' . $ride_request->id);
            $this->findNearbyDriver($ride_request, $radius, $min_amount);
        }

        // Log::info('Driver assignment process completed.');
    }

    protected function findNearbyDriver($ride_request, $radius, $min_amount)
    {
        $unit = $ride_request->distance_unit ?? 'km';
        $unit_value = convertUnitvalue($unit);
        $latitude = $ride_request->start_latitude;
        $longitude = $ride_request->start_longitude;

        // Log::info("Ride Request - Lat: $latitude, Long: $longitude, Unit: $unit_value");

        $cancelled_driver_ids = $ride_request->cancelled_driver_ids ?? [];

        $rejected_bid_driver_ids = is_string($ride_request->rejected_bid_driver_ids) ? json_decode($ride_request->rejected_bid_driver_ids, true) : ($ride_request->rejected_bid_driver_ids ?? []);
        $rejected_bid_driver_ids = is_array($rejected_bid_driver_ids) ? $rejected_bid_driver_ids : [];

        $nearby_driver = User::selectRaw("id, user_type, player_id, latitude, longitude, ( $unit_value * acos( cos( radians($latitude) ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians($longitude) ) + sin( radians($latitude) ) * sin( radians( latitude ) ) ) ) AS distance")
            ->where('user_type', 'driver')
            ->where('status', 'active')
            ->where('is_online', 1)
            ->where('is_available', 1)
            ->where('service_id', $ride_request->service_id)
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

        if ($nearby_driver) {
            // Log::info('Nearby driver found: Driver ID: ' . $nearby_driver->id . ', Distance: ' . $nearby_driver->distance);
            $this->assignDriver($ride_request, $nearby_driver,$radius,$min_amount);
        } else {
            $ride_request->increment('ride_attempt');
            // Log::info('No driver found for ride request ID: ' . $ride_request->id . '. Ride attempt incremented to ' . $ride_request->ride_attempt);

            if ($ride_request->ride_attempt > 5) {
                $ride_request->update([
                    'status' => 'canceled',
                    'cancelled_by' => 'auto',
                ]);

                // Log::info('Ride request ID: ' . $ride_request->id . ' has been auto-canceled after 5 attempts.');
            }
        }
    }

    protected function assignDriver($ride_request, $driver, $radius, $min_amount)
    {
        // Ensure we don't assign the same driver again
        if ($ride_request->riderequest_in_driver_id != $driver->id) {
            $ride_request->update([
                'riderequest_in_driver_id' => $driver->id,
                'riderequest_in_datetime' => Carbon::now()->format('Y-m-d H:i:s'),
                'status' => 'new_ride_requested',
            ]);

            // Log::info('Driver ID: ' . $driver->id . ' assigned to Ride Request ID: ' . $ride_request->id);

            // Send notification to the assigned driver
            $notification_data = [
                'id' => $ride_request->id,
                'type' => 'new_ride_requested',
                'data' => [
                    'rider_id' => $ride_request->rider_id,
                    'rider_name' => optional($ride_request->rider)->display_name ?? '',
                ],
                'message' => __('message.new_ride_requested'),
                'subject' => __('message.ride.new_ride_requested'),
            ];

            try {
                $driver->notify(new CommonNotification($notification_data['type'], $notification_data));
                // Log::info('Notification sent to Driver ID: ' . $driver->id);
            } catch (\Exception $e) {
                Log::error('Error sending notification to Driver ID: ' . $driver->id . '. ' . $e->getMessage());
            }

            $this->startResponseTimer($ride_request, $driver, $radius, $min_amount);
            
            // Log::info('Updating Firebase with Driver ID: ' . $driver->id . ' for Ride Request ID: ' . $ride_request->id);
            $this->updateFirebaseWithDriver($ride_request, $driver);
        } else {
            // Log::info('Driver ID: ' . $driver->id . ' is already assigned to Ride Request ID: ' . $ride_request->id . ', skipping reassignment.');
        }
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

            $rejected_bid_driver_ids = is_string($ride_request->rejected_bid_driver_ids) 
                ? json_decode($ride_request->rejected_bid_driver_ids, true) : [];
            $rejected_bid_driver_ids = array_filter($rejected_bid_driver_ids);

            $updated_nearby_driver_ids = !empty($nearby_driver_ids) && !empty($rejected_bid_driver_ids) 
                ? array_diff($nearby_driver_ids, $rejected_bid_driver_ids)
                : $nearby_driver_ids;

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

            Log::info('Firebase updated successfully for Ride Request ID: ' . $ride_request->id . ' with Driver ID: ' . $driver->id);

        } catch (\Exception $e) {
            Log::error('Error updating Firebase for Ride Request ID: ' . $ride_request->id . ': ' . $e->getMessage());
        }
    }

}
