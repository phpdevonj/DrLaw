<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PaystackTransaction;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\PaymentGateway;
use Illuminate\Support\Facades\Log;
use App\Models\Wallet;
use App\Models\WalletHistory;

class PaystackController extends Controller
{
    public function storeTransaction(Request $request){
        $validator = Validator::make($request->all(), [
            'reference_id' => 'required|string|unique:paystack_transactions,reference_id',
            'email' => 'required|email',
            'amount' => 'required|numeric|min:1',
        ]);

        if ($validator->fails()) {
            return json_message_response($validator->errors(), 422);
        }

        $transaction = PaystackTransaction::create([
            'user_id' => $request->user_id,
            'reference_id' => $request->reference_id,
            'email' => $request->email,
            'amount' => $request->amount,
            'status' => 'pending',
        ]);

        $response = [
            'message' => 'Transaction created successfully',
        ];
        return json_custom_response($response);
    }

    public function updateTransactionStatus(Request $request){
        $validator = Validator::make($request->all(), [
            'reference_id' => 'required|string|exists:paystack_transactions,reference_id',
        ]);

        if ($validator->fails()) {
            return json_message_response($validator->errors(), 422);
        }

        $transaction = PaystackTransaction::where(['reference_id'=> $request->reference_id, 'user_id' => $request->user_id, 'status' => 'pending'])->first();
        if (!$transaction) {
            return json_message_response('Transaction not found', 404);
        }

        $transaction->status = $request->status;
        $transaction->response_data = $request->response_data ?? $transaction->response_data;
        $transaction->last_checked_at = now();
        $transaction->save();

        $response = [
            'message' => 'Transaction status updated successfully',
        ];
        return json_custom_response($response);
    }

    public function handleWebhook(Request $request){
        $paystackSignature = $request->header('x-paystack-signature');
        Log::channel('paystack_webhook')->info('Paystack Webhook Received', ['signature' => $paystackSignature, 'line' => __LINE__]);

        $paystackCredentials = PaymentGateway::where(['type'=>'paystack'])->first();

        $credentialsData = $paystackCredentials->is_test == 1 ? $paystackCredentials->test_value : $paystackCredentials->live_value;

        $secretKey = $credentialsData['secret_key'] ?? null;

        $payload = $request->getContent();
        $calculatedHash = hash_hmac('sha512', $payload, $secretKey);

        // Verify signature
        if ($calculatedHash !== $paystackSignature) {
            Log::channel('paystack_webhook')->warning('Paystack webhook signature mismatch');
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $data = $request->all();
        Log::channel('paystack_webhook')->info('Webhook Payload', $data);

        $event = $data['event'] ?? null;
        $eventData = $data['data'] ?? [];

        $reference = $eventData['reference'] ?? null;

        if (!$reference) {
            Log::channel('paystack_webhook')->warning('Webhook missing reference', ['reference' => $reference, 'line' => __LINE__]);
            return response()->json(['message' => 'Missing reference'], 400);
        }

        if ($event === 'charge.success') {
            $transaction = PaystackTransaction::where(['reference_id' => $reference])->first();
    
            if ($transaction && $transaction->status === 'pending') {
                DB::beginTransaction();
                try {
                    $transaction->status = 'success';
                    $transaction->response_data = $eventData;
                    $transaction->save();
    
                    $wallet = Wallet::firstOrCreate(['user_id' => $transaction->user_id]);
    
                    $wallet->total_amount += $transaction->amount;
                    $wallet->save();
    
                    WalletHistory::create([
                        'user_id' => $transaction->user_id,
                        'datetime' => now(),
                        'type' => 'credit',
                        'transaction_type' => 'topup',
                        'balance' => $wallet->total_amount,
                        'amount' => $transaction->amount,
                    ]);
    
                    DB::commit();
                    Log::channel('paystack_webhook')->error('Webhook charge.success', ['reference_id' => $reference, 'line' => __LINE__]);
                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::channel('paystack_webhook')->error('Webhook charge.success error', ['error' => $e->getMessage(), 'line' => __LINE__]);
                }
            }
            Log::channel('paystack_webhook')->error('Webhook charge.success transaction not found', ['reference_id' => $reference, 'line' => __LINE__]);
        }
    
        if ($event === 'charge.failed') {
            $transaction = PaystackTransaction::where(['reference_id' => $reference])->first();
    
            if ($transaction && $transaction->status === 'pending') {
                $transaction->status = 'failed';
                $transaction->response_data = $eventData;
                $transaction->save();
            }
        }
    
        return response()->json(['status' => 'success'], 200);
    }
}
