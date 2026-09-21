<?php

namespace App\Services;

use App\Models\PaymentGateway;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PayHubService
{
    protected $apiKey;
    protected $apiSecret;
    protected $baseUrl;

    public function __construct()
    {
        $gateway = PaymentGateway::where('type', 'payhub')->first();
        $credentials = $gateway ? ($gateway->is_test == 1 ? $gateway->test_value : $gateway->live_value) : null;

        $this->apiKey = $credentials['api_key'] ?? config('services.payhub.api_key');
        $this->apiSecret = $credentials['api_secret'] ?? config('services.payhub.api_secret');
        $this->baseUrl = rtrim($credentials['url'] ?? config('services.payhub.base_url'), '/');
    }

    /**
     * Create a PayHub transaction and return the hosted checkout response.
     *
     * @param array $data
     * @return array
     */
    public function createTransaction(array $data)
    {
        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'x-api-secret' => $this->apiSecret,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/epay/api/transactions', $data);
        } catch (\Throwable $e) {
            // Covers Guzzle/cURL-level failures (SSL, DNS, timeout, ...) that never reach a
            // response, e.g. RequestException/ConnectException as well as Laravel's own
            // ConnectionException wrapper - none of these should be allowed to crash the request.
            Log::channel('payhub_callback')->error('PayHub Create Transaction connection failed', [
                'request' => $data,
                'error' => $e->getMessage(),
            ]);
            return [];
        }

        Log::channel('payhub_callback')->info('PayHub Create Transaction', [
            'request' => $data,
            'response' => $response->json(),
            'status' => $response->status(),
        ]);

        if ($response->failed()) {
            return [];
        }

        return $response->json() ?? [];
    }
}
