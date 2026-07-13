<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\RideRequest;
use App\Models\Wallet;
use App\Models\WalletHistory;
use App\Traits\PaymentTrait;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaystackController extends Controller
{
    use PaymentTrait;

    public function initializePayment(Request $request)
    {
        $request->validate([
            'ride_request_id' => 'nullable|exists:ride_requests,id',
            'type'            => 'nullable|string|in:wallet_topup',
            'amount'          => 'required_if:type,wallet_topup|numeric|min:1',
        ]);

        $user = auth()->user();
        $email = $user->email;
        $amount = 0;
        $metadata = [
            'user_id' => $user->id,
            'type'    => $request->type ?? 'ride_payment',
        ];

        if ($request->type === 'wallet_topup') {
            $amount = (float) $request->amount;
        } else {
            $request->validate([
                'ride_request_id' => 'required|exists:ride_requests,id',
            ]);
            $rideRequest = RideRequest::with('rider')->find($request->ride_request_id);
            $amount = (float) $rideRequest->total_amount;
            $email = $rideRequest->rider->email;
            $metadata['ride_request_id'] = $rideRequest->id;
        }

        $secretKey = getPaystackSecretKey();

        if (!$secretKey) {
            return json_message_response(__('message.paystack_not_configured'), 400);
        }

        $response = Http::withToken($secretKey)->post('https://api.paystack.co/transaction/initialize', [
            'email' => $email,
            'amount' => $amount * 100, // Paystack amount is in kobo (base unit)
            'metadata' => $metadata,
        ]);

        if ($response->successful()) {
            return json_custom_response($response->json());
        }

        return json_message_response($response->json()['message'] ?? 'Failed to initialize Paystack payment.', 400);
    }

    public function verifyPayment(Request $request)
    {
        $request->validate([
            'reference' => 'required',
        ]);

        $secretKey = getPaystackSecretKey();

        if (!$secretKey) {
            return json_message_response(__('message.paystack_not_configured'), 400);
        }

        $response = Http::withToken($secretKey)->get("https://api.paystack.co/transaction/verify/{$request->reference}");

        if ($response->successful()) {
            $data = $response->json()['data'];
            if ($data['status'] === 'success') {
                $metadata = $data['metadata'];
                $type = $metadata['type'] ?? 'ride_payment';

                if ($type === 'wallet_topup') {
                    $userId = $metadata['user_id'];
                    $amount = $data['amount'] / 100; // Convert from kobo back to base unit

                    $wallet = Wallet::firstOrCreate(['user_id' => $userId]);
                    $wallet->total_amount += $amount;
                    $wallet->save();

                    $currency_code = SettingData('CURRENCY', 'CURRENCY_CODE') ?? 'USD';
                    $currency_data = currencyArray($currency_code);
                    $currency = strtolower($currency_data['code']);

                    WalletHistory::create([
                        'user_id'          => $userId,
                        'type'             => 'credit',
                        'transaction_type' => 'topup',
                        'currency'         => $currency,
                        'amount'           => $amount,
                        'balance'          => $wallet->total_amount,
                        'datetime'         => now(),
                        'data'             => json_encode(['reference' => $request->reference]),
                    ]);

                    return json_message_response(__('message.wallet_topped_up'), 200);
                }

                // Legacy flow / ride_payment flow
                $rideRequestId = $metadata['ride_request_id'] ?? null;
                if (!$rideRequestId) {
                    return json_message_response(__('message.ride_request_id_not_found_metadata'), 400);
                }

                $rideRequest = RideRequest::find($rideRequestId);

                if (!$rideRequest) {
                    return json_message_response(__('message.ride_request_not_found'), 404);
                }

                $payment = Payment::where('ride_request_id', $rideRequestId)->first();
                if (!$payment) {
                     // Create payment if not exists
                     $payment = Payment::create([
                        'rider_id' => $rideRequest->rider_id,
                        'ride_request_id' => $rideRequestId,
                        'datetime' => now(),
                        'total_amount' => $rideRequest->total_amount,
                        'payment_type' => 'paystack',
                        'txn_id' => $request->reference,
                        'payment_status' => 'paid',
                     ]);
                } else {
                    $payment->update([
                        'payment_status' => 'paid',
                        'txn_id' => $request->reference,
                        'payment_type' => 'paystack',
                    ]);
                }

                $this->walletTransaction($rideRequestId);

                return json_message_response(__('message.payment_successful'), 200);
            }
        }

        return json_message_response(__('message.payment_verification_failed'), 400);
    }

    public function initiateTransfer($withdrawRequest)
    {
        $user = $withdrawRequest->user;
        $bankAccount = $user->userBankAccount;

        if (!$bankAccount || !$bankAccount->account_number || !$bankAccount->bank_code) {
            return ['status' => false, 'message' => __('message.bank_account_details_missing')];
        }

        $secretKey = getPaystackSecretKey();

        // 1. Create Transfer Recipient
        $recipientResponse = Http::withToken($secretKey)->post('https://api.paystack.co/transferrecipient', [
            'type' => 'nuban',
            'name' => $bankAccount->account_holder_name ?? $user->display_name,
            'account_number' => $bankAccount->account_number,
            'bank_code' => $bankAccount->bank_code,
            'currency' => strtoupper($withdrawRequest->currency ?? 'NGN'),
        ]);

        if (!$recipientResponse->successful()) {
            return ['status' => false, 'message' => $recipientResponse->json()['message'] ?? 'Failed to create transfer recipient.'];
        }

        $recipientCode = $recipientResponse->json()['data']['recipient_code'];

        // 2. Initiate Transfer
        $transferResponse = Http::withToken($secretKey)->post('https://api.paystack.co/transfer', [
            'source' => 'balance',
            'amount' => $withdrawRequest->amount * 100, // Amount in kobo
            'recipient' => $recipientCode,
            'reason' => 'Wallet Withdrawal',
            'metadata' => [
                'withdraw_request_id' => $withdrawRequest->id,
            ],
        ]);

        if ($transferResponse->successful()) {
            return ['status' => true, 'data' => $transferResponse->json()['data']];
        }

        return ['status' => false, 'message' => $transferResponse->json()['message'] ?? 'Transfer failed.'];
    }
}
