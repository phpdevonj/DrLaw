<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class PayFastService
{
    protected $merchantId;
    protected $merchantKey;
    protected $passphrase;
    protected $env;
    protected $processUrl;
    protected $validateUrl;

    public function __construct()
    {
        $this->merchantId = config('services.payfast.merchant_id');
        $this->merchantKey = config('services.payfast.merchant_key');
        $this->passphrase = config('services.payfast.passphrase');
        $this->env = config('services.payfast.env', 'sandbox');

        if ($this->env === 'live') {
            $this->processUrl = 'https://www.payfast.co.za/eng/process';
            $this->validateUrl = 'https://www.payfast.co.za/eng/query/validate';
        } else {
            $this->processUrl = 'https://sandbox.payfast.co.za/eng/process';
            $this->validateUrl = 'https://sandbox.payfast.co.za/eng/query/validate';
        }
        Log::info('PayFastService Config', [
            'merchantId' => $this->merchantId,
            'merchantKey' => $this->merchantKey,
            'passphrase' => $this->passphrase,
            'env' => $this->env
        ]);
    }

    /**
     * Generate the PayFast hosted checkout URL.
     *
     * @param array $data
     * @return string
     */
    public function generatePaymentUrl(array $data)
    {
        // Must follow "Integration Table" order for Checkout
        $payload = [
            'merchant_id' => $this->merchantId,
            'merchant_key' => $this->merchantKey,
            'return_url' => $data['return_url'],
            'cancel_url' => $data['cancel_url'],
            'notify_url' => $data['notify_url'],
        ];

        if (isset($data['name_first'])) $payload['name_first'] = $data['name_first'];
        if (isset($data['name_last'])) $payload['name_last'] = $data['name_last'];
        if (isset($data['email_address'])) $payload['email_address'] = $data['email_address'];
        
        $payload['m_payment_id'] = $data['m_payment_id'];
        $payload['amount'] = number_format($data['amount'], 2, '.', '');
        $payload['item_name'] = $data['item_name'] ?? 'Wallet Top-up';

        // Checkout signature MUST NOT be sorted alphabetically
        $payload['signature'] = $this->generateSignature($payload, false);

        Log::info('PayFast Checkout Payload', $payload);

        return $this->processUrl . '?' . http_build_query($payload);
    }

    /**
     * Generate a signature for the given data.
     *
     * @param array $data
     * @param bool $sort Whether to sort alphabetically (True for Checkout/API, False for ITN)
     * @param bool $includeEmpty Whether to include empty string values (True for ITN)
     * @return string
     */
    public function generateSignature(array $data, $sort = true, $includeEmpty = false)
    {
        if ($sort) {
            ksort($data);
        }

        $pfOutput = '';
        foreach ($data as $key => $val) {
            if ($key !== 'signature') {
                $v = ($val === null && $includeEmpty) ? '' : $val;
                if ($v !== null && ($v !== '' || $includeEmpty)) {
                    // urlencode in PHP produces lowercase hex (e.g. %3a). 
                    // PayFast requires UPPERCASE hex (e.g. %3A).
                    $encodedVal = urlencode(trim((string)$v));
                    $encodedVal = preg_replace_callback('/%([0-9a-f]{2})/i', function($matches) {
                        return '%' . strtoupper($matches[1]);
                    }, $encodedVal);

                    $pfOutput .= $key . '=' . $encodedVal . '&';
                }
            }
        }

        // Remove last ampersand
        $getString = substr($pfOutput, 0, -1);

        // Append the passphrase if available
        if ($this->passphrase) {
            $getString .= '&passphrase=' . urlencode(trim($this->passphrase));
        }

        Log::info('PayFast Signature Matrix', ['string' => $getString, 'md5' => md5($getString)]);

        return md5($getString);
    }

    /**
     * Validate the ITN signature and transaction status.
     *
     * @param array $postData
     * @return bool
     */
    public function validateItn(array $postData)
    {
        // 1. Validate signature
        $receivedSignature = $postData['signature'] ?? '';
        unset($postData['signature']);

        // ITN signature validation MUST NOT be sorted alphabetically and MUST include empty fields
        $calculatedSignature = $this->generateSignature($postData, false, true);

        if ($receivedSignature !== $calculatedSignature) {
            Log::error('PayFast ITN Signature mismatch', [
                'received' => $receivedSignature,
                'calculated' => $calculatedSignature
            ]);
            return false;
        }

        // 2. Validate with PayFast server
        $response = Http::asForm()->post($this->validateUrl, $postData);

        if ($response->body() !== 'VALID') {
            Log::error('PayFast ITN Validation failed', [
                'response' => $response->body(),
                'status' => $response->status()
            ]);
            return false;
        }

        return true;
    }
}
