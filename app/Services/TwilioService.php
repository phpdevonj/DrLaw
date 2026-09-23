<?php

namespace App\Services;

use Twilio\Rest\Client as TwilioClient;
use Illuminate\Support\Facades\Log;

class TwilioService
{
    protected $client;
    protected $fromNumber;

    public function __construct()
    {
        $sid = config('services.twilio.sid');
        $token = config('services.twilio.auth_token');
        $this->fromNumber = config('services.twilio.from_number');
        
        if ($sid && $token) {
            $this->client = new TwilioClient($sid, $token);
        }
    }

    public function sendSMS($to, $message)
    {
        if (!$this->client) {
            Log::error('Twilio client not initialized. Check credentials in .env');
            return false;
        }

        try {
            $this->client->messages->create($to, [
                'from' => $this->fromNumber,
                'body' => $message
            ]);
            return true;
        } catch (\Exception $e) {
            Log::error('Twilio SMS Sending Failed: ' . $e->getMessage());
            throw $e;
        }
    }
}
