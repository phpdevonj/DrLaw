<?php

namespace App\Services;

use App\Repositories\MobileMoneyRepository;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CashInService
{
    protected $mobileMoneyRepository;
    protected $walletService;
    protected $smobilpayService;

    public function __construct(
        MobileMoneyRepository $mobileMoneyRepository,
        WalletService $walletService,
        SmobilpayService $smobilpayService
        )
    {
        $this->mobileMoneyRepository = $mobileMoneyRepository;
        $this->walletService = $walletService;
        $this->smobilpayService = $smobilpayService;
    }

    protected function resolveResponseMessage(array $payload)
    {
        $errorCode = $payload['error_code'] ?? $payload['errorCode'] ?? $payload['respCode'] ?? null;

        if (isset($payload[0])) {
            $errorCode = $payload[0]['error_code'] ?? $payload[0]['errorCode'] ?? $payload[0]['respCode'] ?? $errorCode;
        }

        $messages = [
            '0' => 'Successful payment',
            '703108' => 'Customer has low balance',
            '703202' => 'Customer rejects the transaction',
            '703201' => 'Customer does not confirm the transaction',
            '703000' => 'The transaction failed',
        ];

        if ($errorCode !== null && array_key_exists((string)$errorCode, $messages)) {
            return $messages[(string)$errorCode];
        }

        $status = strtoupper($payload['status'] ?? ($payload[0]['status'] ?? ''));
        if ($status === 'SUCCESS') {
            return 'Successful payment';
        } elseif ($status === 'FAILED') {
            return 'The transaction failed';
        } elseif ($status === 'PENDING') {
            return 'Transaction is pending';
        }

        return 'Unknown status';
    }

    // ─────────────────────────────────────────────────────────────
    //  Service / Quote helpers (proxied to Smobilpay)
    // ─────────────────────────────────────────────────────────────

    public function getServices(array $params = [])
    {
        $services = $this->smobilpayService->getServices($params);

        if (!is_array($services))
            return [];

        $allowedServices = [
            '20053' => 'MTN MoMo Cash-Out',
            '90010' => 'Express Union Cash Out',
            '30053' => 'Orange Money Cash-Out',
            '100239' => 'Yoomee Money Cashout',
            '600006' => 'Moov Money Cashout Tchad',
            '202411' => 'Moov Money Cashout Gabon',
            '202413' => 'Airtel Money Cashout Gabon',
            '600009' => 'Orange Money RCA Cashout',
        ];

        $allowedServiceIds = array_map('strval', array_keys($allowedServices));

        $filtered = array_values(array_filter($services, function ($service) use ($params, $allowedServiceIds) {
            $serviceId = (string)($service['serviceid'] ?? $service['serviceId'] ?? '');

            // Only return services whose serviceId is in our match array
            if (!in_array($serviceId, $allowedServiceIds, true)) {
                return false;
            }

            // If a specific serviceId is requested, filter to that one only
            if (isset($params['serviceId']) || isset($params['serviceid'])) {
                $requestedId = $params['serviceId'] ?? $params['serviceid'];
                return $serviceId === (string)$requestedId;
            }

            // The /cashout endpoint returns only valid cashout services — no type/status fields.
            // Return all results as-is unless a merchant/type filter is explicitly requested.
            if (isset($params['merchant'])) {
                return strtoupper($service['merchant'] ?? '') === strtoupper($params['merchant']);
            }

            return true;
        }));

        // Add serviceName key to each filtered service
        return array_map(function ($service) use ($allowedServices) {
            $serviceId = (string)($service['serviceid'] ?? $service['serviceId'] ?? '');
            $service['merchant'] = $allowedServices[$serviceId] ?? '';
            return $service;
        }, $filtered);
    }

    public function getServiceDetails(string $serviceId)
    {
        return $this->smobilpayService->getServices(['serviceId' => $serviceId]);
    }

    public function getQuote(array $data)
    {
        if (isset($data['serviceId']) && !isset($data['payItemId'])) {
            $resolved = $this->smobilpayService->resolvePayItemId($data['serviceId']);

            if ($resolved === (string)$data['serviceId']) {
                $data['serviceid'] = $resolved;
            }
            else {
                $data['payItemId'] = $resolved;
            }
            unset($data['serviceId']);
        }

        if (!isset($data['payItemId']) && !isset($data['serviceid'])) {
            throw new \Exception('Service or PayItem could not be resolved. Please provide a valid serviceId.');
        }

        // Smobilpay S3P v2 requires a 'trid' (reference) even for quotes.
        if (!isset($data['trid'])) {
            $data['trid'] = 'QUOTE-' . Str::upper(Str::random(10));
        }

        return $this->smobilpayService->getQuote($data);
    }

    // ─────────────────────────────────────────────────────────────
    //  Step 1 – Initiate cash-in (rider pays → Smobilpay collect)
    // ─────────────────────────────────────────────────────────────

    public function initiatePayment(int $userId, array $data)
    {
        // Ensure we have a reference ID
        $referenceId = $data['trid'] ?? ('CASHIN-' . Str::upper(Str::random(10)));

        // Step 1: Create a pending record in our database
        // As per workflow.txt Step 2: Backend creates transaction (PENDING)
        $record = $this->mobileMoneyRepository->create([
            'user_id' => $userId,
            'reference_id' => $referenceId,
            'amount' => $data['amount'] ?? 0,
            'currency' => $data['currency'] ?? 'XAF',
            'phone_number' => $data['customerPhonenumber'] ?? '',
            'type' => 'cashin',
            'status' => 'pending',
        ]);

        try {
            // New "Single Call" logic: If quoteId is missing, generate it internally
            if (!isset($data['quoteId'])) {
                Log::channel('mobile_money')->info('Smobilpay CashIn: quoteId missing, fetching internally', [
                    'serviceId' => $data['serviceId'] ?? null,
                    'amount' => $data['amount'] ?? null
                ]);

                $quoteData = $this->getQuote([
                    'serviceId' => $data['serviceId'] ?? null,
                    'amount' => $data['amount'] ?? null,
                    'customerPhonenumber' => $data['customerPhonenumber'] ?? null
                ]);

                if (isset($quoteData['quoteId'])) {
                    $data['quoteId'] = $quoteData['quoteId'];
                    // Ensure the resolved payItemId/serviceid is used
                    if (isset($quoteData['payItemId']))
                        $data['payItemId'] = $quoteData['payItemId'];
                    if (isset($quoteData['serviceid']))
                        $data['serviceid'] = $quoteData['serviceid'];
                }
            }

            // Ensure serviceNumber is provided (Smobilpay requires it for some providers).
            // Fallback to customerPhonenumber if not explicitly sent by the app.
            if (!isset($data['serviceNumber']) && isset($data['customerPhonenumber'])) {
                $data['serviceNumber'] = $data['customerPhonenumber'];
            }

            // Step 2: Call Smobilpay Collect
            Log::channel('mobile_money')->info('Smobilpay CashIn: Calling collectPayment', [
                'user_id' => $userId,
                'trid' => $referenceId,
                'quoteId' => $data['quoteId'] ?? 'N/A'
            ]);

            // Smobilpay /collectstd (v2) expects ONLY quoteId and customer details.
            // Extra fields like serviceId/amount can cause "Missing parameters" errors in some versions.
            $collectData = array_intersect_key($data, array_flip([
                'quoteId', 'customerPhonenumber', 'customerEmailaddress',
                'customerName', 'customerAddress', 'serviceNumber'
            ]));
            $collectData['trid'] = $referenceId;

            $response = $this->smobilpayService->collectPayment($collectData);

            // Step 3: Check response for provider transaction ID (ptn)
            if (isset($response['ptn'])) {
                $this->mobileMoneyRepository->update($record, [
                    'provider_transaction_id' => $response['ptn'],
                    'response_payload' => $response,
                ]);
                return $record;
            }

            // If we got an error from Smobilpay
            $errorMessage = $response['devMsg'] ?? ($response['usrMsg'] ?? 'Unknown error from Smobilpay');
            if (stripos($errorMessage, 'regex') !== false || stripos($errorMessage, 'comply') !== false) {
                throw new \InvalidArgumentException('The phone number entered is invalid for the selected operator. Please verify the prefix and try again.');
            }
            if (stripos($errorMessage, 'multiple of') !== false) {
                throw new \InvalidArgumentException($errorMessage);
            }
            throw new \Exception('Smobilpay collection error: ' . $errorMessage);

        }
        catch (\Exception $e) {
            Log::channel('mobile_money')->error('CashIn Initiation Error', [
                'user_id' => $userId,
                'trid' => $referenceId,
                'message' => $e->getMessage()
            ]);
            $this->mobileMoneyRepository->update($record, ['status' => 'failed']);
            throw $e;
        }
    }

    // ─────────────────────────────────────────────────────────────
    //  Step 2 – Verify / poll transaction status
    //           Credits the rider wallet on first SUCCESS
    // ─────────────────────────────────────────────────────────────

    public function verifyTransaction(string $trid)
    {
        $record = $this->mobileMoneyRepository->findByReference($trid);
        if (!$record) {
            throw new \Exception("Cash-in transaction {$trid} not found.");
        }

        $status = $this->smobilpayService->getTransactionStatus($trid);

        if (isset($status[0]['status'])) {
            $remoteStatus = strtoupper($status[0]['status']);
            $providerId = $status[0]['ptn'] ?? $record->provider_transaction_id;

            if ($remoteStatus === 'SUCCESS' && $record->status !== 'completed') {
                $this->mobileMoneyRepository->update($record, [
                    'status' => 'completed',
                    'provider_transaction_id' => $providerId,
                    'response_payload' => array_merge((array)$record->response_payload, ['verify' => $status]),
                    'response_message' => $this->resolveResponseMessage($status),
                ]);

                // Credit the rider's app wallet
                $this->walletService->creditWallet($record->user_id, $record->amount, $record->reference_id);
                return $record->fresh();
            }

            if ($remoteStatus === 'FAILED' && $record->status === 'pending') {
                $this->mobileMoneyRepository->update($record, [
                    'status' => 'failed',
                    'response_payload' => array_merge((array)$record->response_payload, ['verify' => $status]),
                    'response_message' => $this->resolveResponseMessage($status),
                ]);
            }
        }

        return $record->fresh();
    }

    // ─────────────────────────────────────────────────────────────
    //  Webhook handler (called from WebhookController)
    // ─────────────────────────────────────────────────────────────

    public function handleWebhook(array $payload)
    {
        Log::channel('mobile_money')->info('CashIn Webhook received', ['payload' => $payload]);

        $referenceId = !empty($payload['reference']) ? $payload['reference'] : (!empty($payload['trid']) ? $payload['trid'] : null);
        if (!$referenceId) {
            throw new \Exception('Missing reference ID in cashin webhook');
        }

        $record = $this->mobileMoneyRepository->findByReference($referenceId);
        if (!$record || $record->type !== 'cashin') {
            Log::channel('mobile_money')->warning('CashIn Webhook: record not found or wrong type', ['reference' => $referenceId]);
            return null;
        }

        if ($record->status !== 'pending') {
            return $record;
        }

        if (strtoupper($payload['status'] ?? '') === 'SUCCESS') {
            $this->mobileMoneyRepository->update($record, [
                'status' => 'completed',
                'provider_transaction_id' => $payload['provider_transaction_id'] ?? $record->provider_transaction_id,
                'response_payload' => array_merge((array)$record->response_payload, ['webhook' => $payload]),
                'response_message' => $this->resolveResponseMessage($payload),
            ]);

            // Credit rider wallet
            $this->walletService->creditWallet($record->user_id, $record->amount, $record->reference_id);

            $user = \App\Models\User::find($record->user_id);
            if ($user) {
                $user->notify(new \App\Notifications\CommonNotification('wallet_update', [
                    'id' => $record->id,
                    'type' => 'wallet_update',
                    'subject' => 'Wallet Update Successful',
                    'message' => 'Your wallet has been credited successfully.',
                ]));
            }
        }
        else {
            $this->mobileMoneyRepository->update($record, [
                'status' => 'failed',
                'response_payload' => array_merge((array)$record->response_payload, ['webhook' => $payload]),
                'response_message' => $this->resolveResponseMessage($payload),
            ]);

            $user = \App\Models\User::find($record->user_id);
            if ($user) {
                $user->notify(new \App\Notifications\CommonNotification('wallet_update', [
                    'id' => $record->id,
                    'type' => 'wallet_update',
                    'subject' => 'Wallet Update Failed',
                    'message' => 'Wallet update failed: ' . $this->resolveResponseMessage($payload),
                ]));
            }
        }

        return $record->fresh();
    }
}