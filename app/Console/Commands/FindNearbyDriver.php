<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\RideRequest;
use App\Models\User;
use App\Traits\RideRequestTrait;
use App\Models\Setting;
use App\Notifications\CommonNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class FindNearbyDriver extends Command
{
    use RideRequestTrait;

    protected $signature = 'ride:find-nearby-driver';
    protected $description = 'Find nearby driver for ride requests when the initially assigned driver does not respond';

    public function handle()
    {
        $current_time = Carbon::now();
        $time_limit = $current_time->copy()->subMinutes(5);

        $requests = RideRequest::where('is_schedule', 0)
            ->where('status', 'new_ride_requested')
            ->whereBetween('created_at', [$time_limit, $current_time])
            ->get();

        if ($requests->isEmpty()) {
            $this->info('No new ride requests found');
            return;
        }

        foreach ($requests as $request) {
            // Log::info('Processing ride request ID: ' . $request->id);
            $this->processRideRequest($request);
        }

        $this->info('Command executed successfully');
    }

    protected function processRideRequest($request)
    {
        $attempts = 0;
        $max_attempts = 5;
        $riderequest_in_driver_id = [];

        while ($attempts < $max_attempts) {
            $driver = $this->findDriver($request, $riderequest_in_driver_id);

            if ($driver) {
                $request->update([
                    'riderequest_in_driver_id' => $driver->id,
                    'riderequest_in_datetime' => Carbon::now(),
                ]);

                $this->updateFirestore($request, $driver);

                $notification_data = [
                    'id'        => $request->id,
                    'type'      => 'new_ride_requested',
                    'subject'   => __('message.new_ride_requested'),
                    'message'   => __('message.ride.new_ride_requested'),
                ];
                $driver->notify(new CommonNotification($notification_data['type'], $notification_data));

                // Log::info('Driver ID ' . $driver->id . ' notified for ride request ID ' . $request->id);
                $request->refresh();
                if ($request->status === 'accepted') {
                    return;
                } else {
                    $riderequest_in_driver_id[] = $driver->id;
                    $attempts++;
                }
            } else {
                $attempts++;
            }
        }
    }

    protected function findDriver($request, $riderequest_in_driver_id = [])
    {
        $unit = $request->distance_unit ?? 'km';
        $unit_value = convertUnitvalue($unit);
        $radius = Setting::where('type', 'DISTANCE')->where('key', 'DISTANCE_RADIUS')->pluck('value')->first() ?? 50;
        $latitude = $request->start_latitude;
        $longitude = $request->start_longitude;
        $cancelled_driver_ids = array_merge($request->cancelled_driver_ids ?? [], $riderequest_in_driver_id);

        $min_amount = SettingData('wallet', 'min_amount_to_get_ride') ?? null;

        // Log::debug('excluded_drivers_from_find__' . json_encode($cancelled_driver_ids));

        $query = User::selectRaw("id, ( $unit_value * acos( cos( radians($latitude) ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians($longitude) ) + sin( radians($latitude) ) * sin( radians( latitude ) ) ) ) AS distance")
            ->where('user_type', 'driver')
            ->where('status', 'active')
            ->where('is_online', 1)
            ->where('is_available', 1)
            ->where('service_id', $request->service_id)
            ->whereNotIn('id', $cancelled_driver_ids)
            ->having('distance', '<=', $radius)
            ->orderBy('distance', 'asc');

        if ($min_amount !== null) {
            $query->whereHas('userWallet', fn($q) => $q->where('total_amount', '>=', $min_amount));
        }

        $driver = $query->first();

        return $driver;
    }

    protected function updateFirestore($ride_request, $driver)
    {
        try {
            $document_name = 'ride_' . $ride_request->id;
            $firestore = app('firebase.firestore')->database();
            $documentRef = $firestore->collection('rides')->document($document_name);

            $rideData = [
                'driver_id' => $driver->id,
                'on_rider_stream_api_call' => 1,
                'on_stream_api_call' => 0,
                'ride_id' => $ride_request->id,
                'rider_id' => $ride_request->rider_id,
                'status' => $ride_request->status,
                'payment_status' => '',
                'payment_type' => '',
                'tips' => 0,
            ];

            $documentRef->set($rideData, ['merge' => true]);
        } catch (\Exception $e) {
            Log::error('Error updating Firestore: ' . $e->getMessage());
        }
    }
}
