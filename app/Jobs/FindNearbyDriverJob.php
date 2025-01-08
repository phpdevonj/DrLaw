<?php

namespace App\Jobs;

use App\Models\RideRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class FindNearbyDriverJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $rideRequest;

    /**
     * Create a new job instance.
     *
     * @param RideRequest $rideRequest
     * @return void
     */
    public function __construct(RideRequest $rideRequest)
    {
        $this->rideRequest = $rideRequest;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Your logic here
    }
}
