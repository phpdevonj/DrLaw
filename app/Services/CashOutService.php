<?php

namespace App\Services;

use App\Repositories\MobileMoneyRepository;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CashOutService
{
    protected $mobileMoneyRepository;
    protected $walletService;
    protected $smobilpayService;

    public function __construct(
        MobileMoneyRepository $mobileMoneyRepository,
        WalletService $walletService,
        SmobilpayService $smobilpayService
    ) {
        $this->mobileMoneyRepository = $mobileMoneyRepository;
        $this->walletService         = $walletService;
        $this->smobilpayService      = $smobilpayService;
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
    //  Initiate cash-out (driver withdraws wallet balance to MM)
    //  Wallet is debited immediately; payout is confirmed later.
    // ─────────────────────────────────────────────────────────────

    public function initiatePayment(int $userId, array $data)
    {
        $referenceId = $data['trid'] ?? ('CASHOUT-' . Str::upper(Str::random(10)));
        $amount = $data['amount'] ?? 0;

        // 1. Check balance first
        if ($this->walletService->getBalance($userId) < $amount) {
            throw new \Exception('Insufficient wallet balance for withdrawal.');
        }

        // 2. Debit wallet immediately (pending state)
        // We do this BEFORE calling Smobilpay to provide a safe "lock" on funds.
        $this->walletService->debitWallet($userId, $amount, $referenceId);

        $record = $this->mobileMoneyRepository->create([
            'user_id'      => $userId,
            'reference_id' => $referenceId,
            'amount'       => $amount,
            'currency'     => $data['currency'] ?? 'XAF',
            'phone_number' => $data['customerPhonenumber'] ?? '',
            'type'         => 'cashout',
            'status'       => 'pending',
        ]);

        try {
            // 3. Call Smobilpay Payout (Merchant pays Customer)
            Log::channel('mobile_money')->info('Smobilpay CashOut: Calling payoutPayment', [
                'user_id' => $userId,
                'trid' => $referenceId
            ]);

            $response = $this->smobilpayService->payoutPayment(
                array_merge($data, ['trid' => $referenceId])
            );

            if (isset($response['ptn'])) {
                $this->mobileMoneyRepository->update($record, [
                    'provider_transaction_id' => $response['ptn'],
                    'response_payload'        => $response,
                ]);
                return $record;
            }

            $errorMessage = $response['devMsg'] ?? ($response['usrMsg'] ?? 'Unknown payout error');
            if (stripos($errorMessage, 'regex') !== false || stripos($errorMessage, 'comply') !== false) {
                throw new \InvalidArgumentException('The phone number entered is invalid for the selected operator. Please verify the prefix and try again.');
            }
            if (stripos($errorMessage, 'multiple of') !== false) {
                throw new \InvalidArgumentException($errorMessage);
            }
            throw new \Exception('Smobilpay payout error: ' . $errorMessage);

        } catch (\Exception $e) {
            Log::channel('mobile_money')->error('CashOut Initiation Error', [
                'user_id' => $userId,
                'trid' => $referenceId,
                'message' => $e->getMessage()
            ]);
            
            // 4. Reverse debit on initiation failure
            $this->walletService->creditWallet($userId, $amount, 'REFUND-' . $referenceId);
            $this->mobileMoneyRepository->update($record, ['status' => 'failed']);
            throw $e;
        }
    }

    // ─────────────────────────────────────────────────────────────
    //  Verify / poll. No wallet action — wallet was debited on ride
    //  completion; this just tracks payout status.
    // ─────────────────────────────────────────────────────────────

    public function verifyTransaction(string $trid)
    {
        $record = $this->mobileMoneyRepository->findByReference($trid);
        if (!$record) {
            throw new \Exception("Cash-out transaction {$trid} not found.");
        }

        $status = $this->smobilpayService->getTransactionStatus($trid);

        if (isset($status[0]['status'])) {
            $remoteStatus = strtoupper($status[0]['status']);
            $providerId   = $status[0]['ptn'] ?? $record->provider_transaction_id;

            if ($remoteStatus === 'SUCCESS' && $record->status !== 'completed') {
                $this->mobileMoneyRepository->update($record, [
                    'status'                  => 'completed',
                    'provider_transaction_id' => $providerId,
                    'response_payload'        => array_merge((array) $record->response_payload, ['verify' => $status]),
                    'response_message'        => $this->resolveResponseMessage($status),
                ]);
                return $record->fresh();
            }

            if ($remoteStatus === 'FAILED' && $record->status === 'pending') {
                $this->mobileMoneyRepository->update($record, [
                    'status'           => 'failed',
                    'response_payload' => array_merge((array) $record->response_payload, ['verify' => $status]),
                    'response_message' => $this->resolveResponseMessage($status),
                ]);
                // Refund the driver
                $this->walletService->creditWallet($record->user_id, $record->amount, 'REFUND-' . $record->reference_id);
            }
        }

        return $record->fresh();
    }

    // ─────────────────────────────────────────────────────────────
    //  Webhook handler (called from WebhookController)
    // ─────────────────────────────────────────────────────────────

    public function handleWebhook(array $payload)
    {
        Log::channel('mobile_money')->info('CashOut Webhook received', ['payload' => $payload]);

        $referenceId = !empty($payload['reference']) ? $payload['reference'] : (!empty($payload['trid']) ? $payload['trid'] : null);
        if (!$referenceId) {
            throw new \Exception('Missing reference ID in cashout webhook');
        }

        $record = $this->mobileMoneyRepository->findByReference($referenceId);
        if (!$record || $record->type !== 'cashout') {
            Log::channel('mobile_money')->warning('CashOut Webhook: record not found or wrong type', ['reference' => $referenceId]);
            return null;
        }

        if ($record->status !== 'pending') {
            return $record;
        }

        $newStatus = strtoupper($payload['status'] ?? '') === 'SUCCESS' ? 'completed' : 'failed';

        $this->mobileMoneyRepository->update($record, [
            'status'                  => $newStatus,
            'provider_transaction_id' => $payload['provider_transaction_id'] ?? $record->provider_transaction_id,
            'response_payload'        => array_merge((array) $record->response_payload, ['webhook' => $payload]),
            'response_message'        => $this->resolveResponseMessage($payload),
        ]);

        if ($newStatus === 'failed') {
            // Refund the driver
            $this->walletService->creditWallet($record->user_id, $record->amount, 'REFUND-' . $record->reference_id);
            
            $user = \App\Models\User::find($record->user_id);
            if ($user) {
                $user->notify(new \App\Notifications\CommonNotification('wallet_update', [
                    'id' => $record->id,
                    'type' => 'wallet_update',
                    'subject' => 'Wallet Update Failed',
                    'message' => 'Wallet cash out failed: ' . $this->resolveResponseMessage($payload),
                ]));
            }
        } else {
            $user = \App\Models\User::find($record->user_id);
            if ($user) {
                $user->notify(new \App\Notifications\CommonNotification('wallet_update', [
                    'id' => $record->id,
                    'type' => 'wallet_update',
                    'subject' => 'Wallet Update Successful',
                    'message' => 'Your wallet cash out was successful.',
                ]));
            }
        }

        return $record->fresh();
    }
}
