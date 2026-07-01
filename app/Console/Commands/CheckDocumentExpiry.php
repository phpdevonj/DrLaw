<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DriverDocument;
use App\Notifications\CommonNotification;
use App\Notifications\DocumentExpiryNotification;
use App\Notifications\RideNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CheckDocumentExpiry extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'document:check-expiry';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for expiring documents and send notifications';

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
        $this->info('Checking for expiring documents...');
        
        // Days to check: 30, 15, 7, 1, 0
        $daysToCheck = [30, 15, 7, 1,0];

        foreach ($daysToCheck as $days) {
            $targetDate = Carbon::today()->addDays($days)->format('Y-m-d');
            $documents = DriverDocument::whereDate('expire_date', $targetDate)
                ->whereHas('driver', function($q) {
                    $q->where('status', 'active'); // Only notify active drivers
                })
                ->whereHas('document', function($q) {
                    $q->where('is_verified', 1); // Only check active document types
                })
                ->with(['driver', 'document'])
                ->get();
            
        

            $this->info("Found " . $documents->count() . " documents expiring in {$days} days.");

            foreach ($documents as $doc) {
                try {
                    $driver = $doc->driver;
                    if ($driver && $driver->email) {
                        $notification_data = [
                            'id'   => $doc->driver->id,
                            'is_verified_driver' => (int) $doc->driver->is_verified_driver,
                            'type' => 'document_approved',
                            'subject' => __('message.document_expired'),
                            'message' => $this->getNotificationMessage($days, $doc),
                        ];
                
                        $driver->notify(new RideNotification($notification_data));
                        $driver->notify(new DocumentExpiryNotification($doc, $days));
                        $this->info("Notification sent to driver ID: {$driver->id} for document ID: {$doc->id}");
                    }
                } catch (\Exception $e) {
                    Log::error("Failed to send expiry notification for document {$doc->id}: " . $e->getMessage());
                    $this->error("Failed to send notification for document {$doc->id}");
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Mark Drivers as Pending if Document Already Expired
        |--------------------------------------------------------------------------
        */

        $expiredDocuments = DriverDocument::whereDate('expire_date', '<', Carbon::today())
            ->whereHas('driver', function ($q) {
                $q->where('status', 'active'); // Only active drivers
            })
            ->with('driver')
            ->get();

        $this->info("Found " . $expiredDocuments->count() . " already expired documents.");

        foreach ($expiredDocuments as $doc) {
            try {

                $driver = $doc->driver;

                if ($driver) {

                    $doc->is_verified = 3;
                    $doc->save();
                    
                    $driver->status = 'pending';
                    $driver->save();

                    $notification_data = [
                        'id'   => $doc->driver->id,
                        'is_verified_driver' => (int) $doc->driver->is_verified_driver,
                        'type' => 'document_expired',
                        'subject' => __('message.document_expired'),
                        'message' => __('message.driver_expired_document', [ 'document' => $doc->document->name ]),
                    ];
                
                    $driver->notify(new RideNotification($notification_data));
                    $driver->notify(new DocumentExpiryNotification($doc, -1));
                    $this->info("Driver ID {$driver->id} marked as pending due to expired document ID {$doc->id}");
                }
            } catch (\Exception $e) {
                Log::error("Failed to mark driver pending for document {$doc->id}: " . $e->getMessage());
                $this->error("Failed to update driver {$doc->driver_id}");
            }
        }

        $this->info('Document expiry check completed.');

        return 0;
    }

    public function getNotificationMessage($daysLeft, $doc)
    {
        $docName = $doc->document->name;
        
        if ($daysLeft == 0) {
            return "Your document '{$docName}' has expired today.";
        } elseif ($daysLeft < 0) {
             return "Your document '{$docName}' expired " . abs($daysLeft) . " days ago.";
        } else {
            return "Your document '{$docName}' will expire in {$daysLeft} days.";
        }
    }
}
