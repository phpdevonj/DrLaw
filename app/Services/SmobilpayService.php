<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SmobilpayService
{
    protected $publicKey;
    protected $secretKey;
    protected $baseUrl;
    protected $payItemId;

    public function __construct()
    {
        $this->publicKey = env('MOBILE_MONEY_PUBLIC_KEY');
        $this->secretKey = env('MOBILE_MONEY_SECRET_KEY');
        $this->baseUrl = rtrim(env('MOBILE_MONEY_URL'), '/');
        $this->payItemId = env('MOBILE_MONEY_PAY_ITEM_ID', 'SPAY-ZA-MTN-CASHIN');
    }

    public function getPayItemId()
    {
        return $this->payItemId;
    }

    public function getAuthHeader($method, $url, $payload = [])
    {
        $timestamp = time();
        $nonce = Str::random(16);
        $method = strtoupper($method);

        $parseUrl = parse_url($url);
        // S3P v2 requires the full URL for the base string
        $cleanUrl = $parseUrl['scheme'] . "://" . $parseUrl['host'] . $parseUrl['path'];

        $authParams = [
            's3pAuth_nonce' => $nonce,
            's3pAuth_signature_method' => 'HMAC-SHA1',
            's3pAuth_timestamp' => $timestamp,
            's3pAuth_token' => $this->publicKey,
        ];

        // Combine auth params with request parameters
        $params = array_merge($authParams, (array)$payload);

        // Sorting must be strictly alphabetical
        uksort($params, 'strcmp');

        // IMPORTANT: Use single-encoding for parameters in the base string.
        // Special characters like '@' should be encoded only once by the final rawurlencode().
        $query = [];
        foreach ($params as $key => $value) {
            $query[] = $key . '=' . $value;
        }
        $paramString = implode('&', $query);

        // Smobilpay S3P v2: Method & Encoded(URL) & Encoded(ParamString)
        $baseString = $method . "&" . rawurlencode($cleanUrl) . "&" . rawurlencode($paramString);

        $signature = base64_encode(hash_hmac('sha1', $baseString, $this->secretKey, true));

        // Format is s3pAuth,key="value",... with NO SPACES
        $header = sprintf(
            's3pAuth,s3pAuth_nonce="%s",s3pAuth_signature="%s",s3pAuth_signature_method="HMAC-SHA1",s3pAuth_timestamp="%s",s3pAuth_token="%s"',
            $nonce,
            $signature,
            $timestamp,
            $this->publicKey
        );

        return $header;
    }

    public function getServices(array $params = [])
    {
        $url = "{$this->baseUrl}/cashout";
        $authHeader = $this->getAuthHeader('GET', $url, $params);
        $response = Http::timeout(30)->withHeaders([
            'Authorization' => $authHeader,
            'x-api-version' => '3',
        ])->get($url, $params);
        return $response->json();
    }

    public function getTransactionStatus(string $trid)
    {
        $params = ['trid' => $trid];
        $url = "{$this->baseUrl}/verifytx";
        $authHeader = $this->getAuthHeader('GET', $url, $params);

        $response = Http::timeout(30)->withHeaders([
            'Authorization' => $authHeader,
            'x-api-version' => '3',
        ])->get($url, $params);

        return $response->json();
    }

    public function getProducts(array $params = [])
    {
        $url = "{$this->baseUrl}/product";
        $authHeader = $this->getAuthHeader('GET', $url, $params);

        $response = Http::timeout(30)->withHeaders([
            'Authorization' => $authHeader,
            'x-api-version' => '3',
        ])->get($url, $params);

        return $response->json();
    }

    public function getCashoutProducts(string $serviceId)
    {
        $params = ['serviceid' => $serviceId];
        $url = "{$this->baseUrl}/cashout";
        $authHeader = $this->getAuthHeader('GET', $url, $params);

        $response = Http::timeout(30)->withHeaders([
            'Authorization' => $authHeader,
            'x-api-version' => '3',
        ])->get($url, $params);

        return $response->json();
    }

    public function resolvePayItemId(string $serviceId, string $amountType = 'CUSTOM'): ?string
    {
        // 1. Try regular products (for bills, etc.)
        $products = $this->getProducts();
        $allProducts = is_array($products) ? $products : [];

        // 2. Also check cashout-specific products (for MoMo/Orange)
        $cashoutProducts = $this->getCashoutProducts($serviceId);
        if (is_array($cashoutProducts)) {
            $allProducts = array_merge($allProducts, $cashoutProducts);
        }

        // First pass: matching serviceId + preferred amountType
        foreach ($allProducts as $product) {
            if (
            isset($product['serviceid'], $product['payItemId']) &&
            (string)$product['serviceid'] === (string)$serviceId &&
            strtoupper($product['amountType'] ?? '') === strtoupper($amountType)
            ) {
                return $product['payItemId'];
            }
        }

        // Second pass: any match for serviceId
        foreach ($allProducts as $product) {
            if (
            isset($product['serviceid'], $product['payItemId']) &&
            (string)$product['serviceid'] === (string)$serviceId
            ) {
                return $product['payItemId'];
            }
        }

        // Third pass: fallback to the serviceId itself
        return $serviceId;
    }

    public function getQuote(array $data)
    {
        $url = "{$this->baseUrl}/quotestd";
        $authHeader = $this->getAuthHeader('POST', $url, $data);

        Log::channel('mobile_money')->debug('Smobilpay API: quotestd Request', [
            'url' => $url,
            'headers' => ['x-api-version' => '3', 'Authorization' => $authHeader],
            'payload' => $data
        ]);

        $response = Http::timeout(10)->withHeaders([
            'x-api-version' => '3',
            'Authorization' => $authHeader,
        ])->post($url, $data);

        $result = $response->json();
        Log::channel('mobile_money')->debug('Smobilpay API: quotestd Response', ['result' => $result]);

        return $result;
    }

    public function collectPayment(array $data)
    {
        $url = "{$this->baseUrl}/collectstd";
        $authHeader = $this->getAuthHeader('POST', $url, $data);

        Log::channel('mobile_money')->debug('Smobilpay API: collectstd Request', [
            'url' => $url,
            'headers' => ['x-api-version' => '3', 'Authorization' => $authHeader],
            'payload' => $data
        ]);


        $response = Http::timeout(60)->withHeaders([
            'x-api-version' => '3',
            'Authorization' => $authHeader,
        ])->post($url, $data);

        $result = $response->json();
        Log::channel('mobile_money')->debug('Smobilpay API: collectstd Response', ['result' => $result]);

        return $result;
    }

    public function payoutPayment(array $data)
    {
        $url = "{$this->baseUrl}/payoutstd";
        $authHeader = $this->getAuthHeader('POST', $url, $data);

        Log::channel('mobile_money')->debug('Smobilpay API: payoutstd Request', [
            'url' => $url,
            'headers' => ['x-api-version' => '3', 'Authorization' => $authHeader],
            'payload' => $data
        ]);

        $response = Http::timeout(10)->withHeaders([
            'x-api-version' => '3',
            'Authorization' => $authHeader,
        ])->post($url, $data);

        $result = $response->json();
        Log::channel('mobile_money')->debug('Smobilpay API: payoutstd Response', ['result' => $result]);

        return $result;
    }
}