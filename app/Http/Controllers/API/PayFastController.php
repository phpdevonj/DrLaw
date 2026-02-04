<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\PayFastTransaction;
use App\Models\Wallet;
use App\Models\WalletHistory;
use App\Services\PayFastService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PayFastController extends Controller
{
    protected $payFastService;

    public function __construct(PayFastService $payFastService)
    {
        $this->payFastService = $payFastService;
    }

    /**
     * Create a new PayFast payment and return the hosted checkout URL.
     */
    public function createPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:1',
        ]);

        if ($validator->fails()) {
            return json_message_response($validator->errors(), 422);
        }

        $user = auth()->user();
        $mPaymentId = Str::uuid()->toString();

        // Create a pending transaction
        $transaction = PayFastTransaction::create([
            'user_id' => $user->id,
            'm_payment_id' => $mPaymentId,
            'amount' => $request->amount,
            'status' => 'initiated',
        ]);

        $appUrl = rtrim(config('app.url'), '/');
        $paymentData = [
            'amount' => $request->amount,
            'm_payment_id' => $mPaymentId,
            'return_url' => $appUrl . '/payfast/success',
            'cancel_url' => $appUrl . '/payfast/cancel',
            'notify_url' => $appUrl . '/payfast/itn',
            'email_address' => $user->email,
            'item_name' => 'Wallet Top-up'
        ];

        $paymentUrl = $this->payFastService->generatePaymentUrl($paymentData);

        return json_custom_response([
            'status' => true,
            'm_payment_id' => $mPaymentId,
            'payment_url' => $paymentUrl,
            'message' => 'Payment URL generated successfully.'
        ]);
    }

    /**
     * Handle the ITN callback from PayFast.
     */
    public function itnCallback(Request $request)
    {
        $data = $request->all();
        Log::channel('payfast_itn')->info('PayFast ITN Received', $data);

        // 1. Validate ITN
        if (!$this->payFastService->validateItn($data)) {
            Log::channel('payfast_itn')->error('Invalid PayFast ITN validation');
            return response()->json(['status' => 'error', 'message' => 'Validation failed'], 400);
        }

        $mPaymentId = $data['m_payment_id'] ?? null;
        $pfPaymentId = $data['pf_payment_id'] ?? null;
        $amount = $data['amount_gross'] ?? 0;
        $paymentStatus = $data['payment_status'] ?? '';

        if (!$mPaymentId) {
            Log::channel('payfast_itn')->error('Missing m_payment_id in ITN');
            return response()->json(['status' => 'error', 'message' => 'Missing ID'], 400);
        }

        $transaction = PayFastTransaction::where('m_payment_id', $mPaymentId)->first();

        if (!$transaction) {
            Log::channel('payfast_itn')->error('Transaction not found', ['m_payment_id' => $mPaymentId]);
            return response()->json(['status' => 'error', 'message' => 'Transaction not found'], 404);
        }

        // 2. Verify Amount (Step 3 in workflow)
        if (number_format((float)$transaction->amount, 2, '.', '') !== number_format((float)$amount, 2, '.', '')) {
            Log::channel('payfast_itn')->error('Amount mismatch', [
                'expected' => $transaction->amount,
                'received' => $amount
            ]);
            return response()->json(['status' => 'error', 'message' => 'Amount mismatch'], 400);
        }

        // Update transaction payload
        $transaction->raw_payload = $data;
        $transaction->pf_payment_id = $pfPaymentId;

        if ($paymentStatus === 'COMPLETE') {
            if ($transaction->status !== 'complete') {
                DB::beginTransaction();
                try {
                    $transaction->status = 'complete';
                    $transaction->credited_at = now();
                    $transaction->save();

                    // Credit Wallet
                    $wallet = Wallet::firstOrCreate(['user_id' => $transaction->user_id]);
                    $wallet->total_amount += $transaction->amount;
                    $wallet->save();

                    // Create Wallet History
                    WalletHistory::create([
                        'user_id' => $transaction->user_id,
                        'datetime' => now(),
                        'type' => 'credit',
                        'transaction_type' => 'topup',
                        'currency' => 'ZAR', // PayFast is South African
                        'balance' => $wallet->total_amount,
                        'amount' => $transaction->amount,
                        'description' => 'Wallet Top-up via PayFast'
                    ]);

                    DB::commit();
                    Log::channel('payfast_itn')->info('Wallet successfully credited via PayFast', ['m_payment_id' => $mPaymentId]);
                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::channel('payfast_itn')->error('Error updating wallet from PayFast ITN', ['error' => $e->getMessage()]);
                    return response()->json(['status' => 'error', 'message' => 'Internal error'], 500);
                }
            } else {
                Log::channel('payfast_itn')->info('Transaction already completed', ['m_payment_id' => $mPaymentId]);
            }
        } elseif ($paymentStatus === 'CANCELLED') {
            $transaction->status = 'cancelled';
            $transaction->save();
        } elseif ($paymentStatus === 'FAILED') {
            $transaction->status = 'failed';
            $transaction->save();
        }

        return response()->json(['status' => 'success'], 200);
    }

    /**
     * Success Redirection (for Mobile UI)
     */
    public function paymentSuccess(Request $request)
    {
        return view('payment.success', ['gateway' => 'PayFast']);
    }

    /**
     * Cancel Redirection (for Mobile UI)
     */
    public function paymentCancel(Request $request)
    {
        return view('payment.cancel', ['gateway' => 'PayFast']);
    }
}
