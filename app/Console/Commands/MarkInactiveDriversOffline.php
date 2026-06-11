<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class MarkInactiveDriversOffline extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'drivers:mark-inactive-offline';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark drivers offline if they are inactive for more than 30 minutes';

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
        $limitTime = Carbon::now()->subMinutes(60);

        // Atomic update = completely safe from race conditions
        $affected = User::where('user_type', 'driver')
            ->where('is_online', 1)
            ->where('is_available', 1)
            ->where('last_actived_at', '<', $limitTime)
            ->update([
                'is_online' => 0,
                'updated_at' => now(),
            ]);

        Log::channel('mark_inactive_driver_offline')->info("Total drivers marked offline: " . $affected);
    }
}
