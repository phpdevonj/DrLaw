<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\RideRequest;
use App\Models\User;
use App\Models\RideRequestHistory;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Notifications\CommonNotification;

class AutoCancelArrivedRides extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rides:auto-cancel-arrived';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto cancel rides stuck in Arrived status for more than 30 minutes';

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
            // Get timestamp from 30 minutes ago
            $cutoff = Carbon::now()->subMinutes(30);

            // Find rides that are still in Arrived status after 30 mins
            $rides = RideRequest::where('status', 'arrived')
                              ->where('updated_at', '<=', $cutoff)
                              ->get();

            Log::channel('cancel_arrived_rides')->info("Auto cancel arrived rides completed successfully. Total rides processed: " . count($rides));

            foreach($rides as $ride) {
                $this->cancelArrivedRide($ride);            
            }
            
            return 0;

        } catch(\Exception $e) {
            Log::channel('cancel_arrived_rides')->error("Error in auto canceling arrived rides: " . $e->getMessage());
            return 1;
        }
    }

    private function cancelArrivedRide($ride)
    {
        try {
            $reason = 'Automatically canceled - driver did not start the ride after arrival';
            $riderMessage = 'Your ride has been automatically canceled as the driver did not start the ride after arrival.';
            $historyMessage = 'Ride automatically canceled - driver did not start ride after arrival';
            $historyReason = 'Driver did not start ride after arrival';

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
            $this->notifyRider($ride, $riderMessage, $reason);

            // Delete from Firebase
            $this->deleteRideFromFirebase($ride->id);

            // Driver canceled → release hold only if Stripe is configured in DB
            if (hasStripeKeys() && $ride->payment_type === 'card' && !empty($ride->held_payment_intent_id)) {
                cancelStripePayment($ride->held_payment_intent_id);
            }

        } catch(\Exception $e) {
            Log::channel('cancel_arrived_rides')->error("Error canceling ride {$ride->id}: " . $e->getMessage());
            throw $e;
        }
    }

    private function notifyRider($ride, $message, $reason): void 
    {
        if ($rider = User::find($ride->rider_id)) {
            $notificationData = [
                'id' => $ride->id,
                'type' => 'canceled',
                'data' => [
                    'ride_id' => $ride->id,
                    'canceled_by' => 'system'
                ],
                'message' => $message,
                'subject' => $reason,
            ];
            
            $rider->notify(new CommonNotification($notificationData['type'], $notificationData));
        }
    }

    protected function deleteRideFromFirebase(int $rideId): void
    {
        try {
            $document_name = 'ride_' . $rideId;
            $firestore = app('firebase.firestore')->database();
            $documentRef = $firestore->collection('rides')->document($document_name);
            
            if($documentRef->snapshot()->exists()) {
                $documentRef->delete();
                Log::channel('cancel_arrived_rides')->info("[canceled overdue arrived] Ride deleted from Firebase successfully for Ride ID: {$rideId}");
            } else {
                Log::channel('cancel_arrived_rides')->info("[canceled overdue arrived] Ride not found in Firebase for Ride ID: {$rideId}");
            }
        } catch (\Exception $e) {
            Log::channel('cancel_arrived_rides')->error("[canceled overdue arrived] Error deleting Ride from Firebase for Ride ID: {$rideId}: {$e->getMessage()}");
        }
    }
}
