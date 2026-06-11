<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\RideRequest;
use App\Models\User;
use App\Notifications\CommonNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Models\RideRequestHistory;

class CancelOverdueScheduledRides extends Command
{
    protected $signature = 'scheduleride:cancel-overdue-rides';
    protected $description = 'Cancel scheduled rides that are overdue and have no driver assigned';

    public function handle()
    {
        try {
            $now = Carbon::now();

            // Get scheduled rides that are overdue (scheduled time has passed) and have no driver assigned
            $overdueRides = RideRequest::where('is_schedule', 1)
                ->whereIn('status', ['scheduled', 'driver_accepted','new_ride_requested'])
                ->where('scheduled_at', '<', $now)
                ->get();

            // Get accepted rides where driver didn't change status to arriving within 15 minutes after scheduled time
            $acceptedRides = RideRequest::where('is_schedule', 1)
                ->where('status', 'accepted')
                ->where('scheduled_at', '<=', $now->subMinutes(15))
                ->get();

            $totalCount = $overdueRides->count() + $acceptedRides->count();

            Log::channel('cancel_overdue_schedule_rides')->info("Cron started: processing {$totalCount} overdue scheduled rides [Line: " . __LINE__ . "]");

            foreach ($overdueRides as $ride) {
                $this->cancelOverdueRide($ride, 'no_driver');
            }

            foreach ($acceptedRides as $ride) {
                $this->cancelOverdueRide($ride, 'driver_not_arrived');
            }
            
            return 0;
        } catch (\Exception $e) {
            Log::channel('cancel_overdue_schedule_rides')->error("Error canceling overdue scheduled rides: {$e->getMessage()} [Line: " . __LINE__ . "]");
            return 1;
        }
    }

    private function cancelOverdueRide($ride, $type = 'no_driver')
    {

        if ($type === 'driver_not_arrived') {
            $reason = 'Automatically canceled - driver did not arrive within 15 minutes after scheduled time';
            $riderMessage = 'Your scheduled ride has been automatically canceled because the driver did not arrive within 15 minutes of the scheduled time.';
            $historyMessage = 'Ride automatically canceled - driver did not arrive within 15 minutes after scheduled time';
            $historyReason = 'Driver did not arrive within 15 minutes after scheduled time';
        } else {
            $reason = 'Automatically canceled - no driver assigned and scheduled time passed';
            $riderMessage = 'Your scheduled ride has been automatically canceled as no driver was available at the scheduled time.';
            $historyMessage = 'Ride automatically canceled - no driver assigned and scheduled time passed';
            $historyReason = 'No driver assigned and scheduled time passed';
        }

        // Update ride status to canceled
        $ride->update([
            'status' => 'canceled',
            'cancel_by' => 'auto',
            'reason' => $reason
        ]);

        // Add entry to ride history
        $historyData = [
            'datetime' => Carbon::now()->format('Y-m-d H:i:s'),
            'history_type' => 'canceled',
            'history_message' => $historyMessage,
            'ride_request_id' => $ride->id,
            'history_data' => json_encode([
                'canceled_by' => 'system',
                'reason' => $historyReason
            ])
        ];
        RideRequestHistory::create($historyData);

        // Send notification to rider
        if ($rider = User::find($ride->rider_id)) {
            $notificationData = [
                'id' => $ride->id,
                'type' => 'ride_canceled',
                'data' => [
                    'ride_id' => $ride->id,
                    'canceled_by' => 'system'
                ],
                'message' => $riderMessage,
                'subject' => 'Scheduled Ride Canceled',
            ];
            
            $rider->notify(new CommonNotification($notificationData['type'], $notificationData));
        }

        // Delete from Firebase
        $this->deleteRideFromFirebase($ride->id);

        Log::channel('cancel_overdue_schedule_rides')->info("Automatically canceled overdue scheduled ride #{$ride->id} [Line: " . __LINE__ . "]");
    }

    protected function deleteRideFromFirebase($rideId)
    {
        try {
            $document_name = 'ride_' . $rideId;
            $firestore = app('firebase.firestore')->database();
            $documentRef = $firestore->collection('rides')->document($document_name);
            if($documentRef->snapshot()->exists()) {
                $documentRef->delete();
                Log::channel('cancel_overdue_schedule_rides')->info("[canceled overdue scheduled] Ride deleted from Firebase successfully for Ride ID: {$rideId} [Line: " . __LINE__ . "]");
            }else {
                Log::channel('cancel_overdue_schedule_rides')->info("[canceled overdue scheduled] Ride not found in Firebase for Ride ID: {$rideId} [Line: " . __LINE__ . "]");
            }
        } catch (\Exception $e) {
            Log::channel('cancel_overdue_schedule_rides')->error("[canceled overdue scheduled] Error deleting Ride from Firebase for Ride ID: {$rideId}: {$e->getMessage()} [Line: " . __LINE__ . "]");
        }
    }
}