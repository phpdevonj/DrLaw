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
            // Get scheduled rides that are due within the next 15 minutes
            $scheduledRides = RideRequest::where('is_schedule', 1)->whereIn('status', ['scheduled','driver_accepted'])->where('scheduled_at', '<=', Carbon::now()->addMinutes(15))->where('scheduled_at', '>=', Carbon::now())->get();

            foreach ($scheduledRides as $ride) {
                // Check if driver is assigned
                if ($ride->driver_id) {
                    // Send notification to driver
                    $driver = User::find($ride->driver_id);
                    if($driver){
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
                        }else{
                            \Log::info('Document does not exist: ' . $document_name);
                        }
                        $firebaseData->set($rideData);

                        // Send notification
                        $notification_data = [
                            'id' => $ride->id,
                            'type' => 'scheduled_ride_reminder',
                            'data' => [
                                'rider_id' => $ride->rider_id,
                                'rider_name' => optional($ride->rider)->display_name ?? '',
                            ],
                            'message' => 'You have a scheduled ride coming up in ' .Carbon::now()->diffInMinutes($ride->scheduled_at).' minutes.',
                            'subject' => 'Upcoming Scheduled Ride',
                        ];
                        
                        $driver->notify(new CommonNotification($notification_data['type'], $notification_data));
                        $ride->driver->update(['is_available' => 0]);
                        $ride->update(['status'=>'accepted']);

                        // store ride history
                        $data['datetime'] = date('Y-m-d H:i:s');
                        $data['history_type'] = 'accepted';
                        $data['history_message'] = __('message.ride.accepted');
                        $data['ride_request_id'] = $ride->id;
                        $history_data = [
                            'driver_id' => $ride->driver_id,
                            'driver_name' => optional($ride->driver)->display_name ?? '',
                        ];
                        $data['history_data'] = json_encode($history_data);
                        if( $data['history_type'] != null ) {
                            RideRequestHistory::create($data);
                        }
                        
                        Log::info('Scheduled ride notification sent to driver #'.$driver->id.' for ride #'.$ride->id);
                    }
                }else{
                    // driver not assined to schedule ride.
                    $this->acceptDeclinedRideRequest($ride, []);
                }
            }
            return 0;
        } catch (\Exception $e) {
            Log::error('Error processing scheduled rides: ' . $e->getMessage());
            return 1;
        }
    }
}
