<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\PayHubTransaction;
use App\Models\Wallet;
use App\Models\WalletHistory;
use App\Services\PayHubService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PayHubController extends Controller
{
    protected $payHubService;

    public function __construct(PayHubService $payHubService)
    {
        $this->payHubService = $payHubService;
    }

    /**
     * Create a new PayHub payment and return the hosted checkout URL.
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
        $referenceId = Str::uuid()->toString();

        // Create a pending transaction
        $transaction = PayHubTransaction::create([
            'user_id' => $user->id,
            'reference_id' => $referenceId,
            'amount' => $request->amount,
            'status' => 'initiated',
        ]);

        $appUrl = rtrim(config('app.url'), '/');

        $paymentData = [
            'amount' => (string) $request->amount,
            'redirectUrl' => $appUrl . '/payhub/redirect',
            'callbackUrl' => $appUrl . '/payhub/callback',
            // metadata1 carries our own unique reference so the callback can be matched back to this transaction
            'metadata1' => $referenceId,
            'metadata2' => (string) $user->id,
            'metadata3' => 'wallet_topup',
        ];

        $result = $this->payHubService->createTransaction($paymentData);

        if (empty($result['paymentUrl'])) {
            Log::channel('payhub_callback')->error('PayHub transaction creation failed', [
                'reference_id' => $referenceId,
                'response' => $result,
            ]);
            return json_message_response('Unable to initiate PayHub payment.', 500);
        }

        $transaction->transaction_id = $result['transactionId'] ?? null;
        $transaction->raw_payload = $result;
        $transaction->save();

        return json_custom_response([
            'status' => true,
            'reference_id' => $referenceId,
            'transaction_id' => $transaction->transaction_id,
            'payment_url' => $result['paymentUrl'],
            'message' => 'Payment URL generated successfully.'
        ]);
    }

    /**
     * Handle the server-to-server callback from PayHub.
     */
    public function callback(Request $request)
    {
        $data = $request->all();
        Log::channel('payhub_callback')->info('PayHub Callback Received', $data);

        $referenceId = $data['metadata1'] ?? null;
        $status = strtoupper($data['status'] ?? '');
        $amount = $data['amount'] ?? 0;

        if (!$referenceId) {
            Log::channel('payhub_callback')->error('Missing metadata1 (reference id) in PayHub callback');
            return response()->json(['status' => 'error', 'message' => 'Missing reference'], 400);
        }

        $transaction = PayHubTransaction::where('reference_id', $referenceId)->first();

        if (!$transaction) {
            Log::channel('payhub_callback')->error('PayHub transaction not found', ['reference_id' => $referenceId]);
            return response()->json(['status' => 'error', 'message' => 'Transaction not found'], 404);
        }

        if (number_format((float) $transaction->amount, 2, '.', '') !== number_format((float) $amount, 2, '.', '')) {
            Log::channel('payhub_callback')->error('PayHub amount mismatch', [
                'reference_id' => $referenceId,
                'expected' => $transaction->amount,
                'received' => $amount,
            ]);
            return response()->json(['status' => 'error', 'message' => 'Amount mismatch'], 400);
        }

        $transaction->raw_payload = $data;
        $transaction->epay_transaction_id = $data['epayTransactionId'] ?? $transaction->epay_transaction_id;

        if ($status === 'SUCCESS') {
            if ($transaction->status !== 'complete') {
                DB::beginTransaction();
                try {
                    $transaction->status = 'complete';
                    $transaction->credited_at = now();
                    $transaction->save();

                    $currency_code = SettingData('CURRENCY', 'CURRENCY_CODE') ?? 'USD';

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
                        'currency' => $currency_code,
                        'balance' => $wallet->total_amount,
                        'amount' => $transaction->amount,
                        'description' => 'Wallet Top-up via PayHub'
                    ]);

                    DB::commit();
                    Log::channel('payhub_callback')->info('Wallet successfully credited via PayHub', ['reference_id' => $referenceId]);
                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::channel('payhub_callback')->error('Error updating wallet from PayHub callback', ['error' => $e->getMessage()]);
                    return response()->json(['status' => 'error', 'message' => 'Internal error'], 500);
                }
            } else {
                $transaction->save();
                Log::channel('payhub_callback')->info('PayHub transaction already completed', ['reference_id' => $referenceId]);
            }
        } elseif (in_array($status, ['FAILED', 'CANCELLED', 'CANCELED'])) {
            $transaction->status = 'failed';
            $transaction->save();
        } else {
            $transaction->save();
        }

        return response()->json(['status' => 'success'], 200);
    }

    /**
     * Redirect landing page shown to the user after leaving the PayHub hosted checkout.
     * The wallet is credited via the callback webhook, not from this redirect.
     */
    public function paymentRedirect(Request $request)
    {
        $status = strtoupper($request->query('status', ''));

        if (in_array($status, ['FAILED', 'CANCELLED', 'CANCELED'])) {
            return view('payment.cancel', ['gateway' => 'PayHub']);
        }

        return view('payment.success', ['gateway' => 'PayHub']);
    }
}
