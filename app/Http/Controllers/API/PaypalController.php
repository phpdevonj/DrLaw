<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletHistory;
use App\Models\UserCard;
use App\Services\PaypalService;
use App\Notifications\CommonNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;

class PaypalController extends Controller
{
    protected $paypalService;

    public function __construct(PaypalService $paypalService)
    {
        $this->paypalService = $paypalService;
    }

    /**
     * Initialize PayPal payment for wallet recharge.
     */
    public function createPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:1',
            'currency' => 'nullable|string|max:3',
            'save_card' => 'nullable|boolean',
            'card_id' => 'nullable|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => __('message.validation_error'),
                'errors' => $validator->errors()
            ], 422);
        }

        $userId = auth()->user()->id;
        $amount = $request->amount;
        $currency = $request->input('currency', 'USD');
        $saveCard = (bool) $request->input('save_card', false);
        $cardId = $request->card_id;

        try {
            // Check if user is paying with a saved card
            if ($cardId) {
                $card = UserCard::where('user_id', $userId)->where('gateway', 'paypal')->find($cardId);
                if (!$card) {
                    return response()->json([
                        'status' => false,
                        'message' => __('message.saved_card_not_found')
                    ], 404);
                }

                // Create order referencing the vaulted card token
                $order = $this->paypalService->createOrder($userId, $amount, $currency, false, $card->vault_id);

                // Attempt immediate capture of the vaulted card order
                $capture = $this->paypalService->captureOrder($order['id']);
                
                if (isset($capture['status']) && $capture['status'] === 'COMPLETED') {
                    $credited = $this->processCaptureAndCredit($capture, $order['id']);
                    return response()->json([
                        'status' => true,
                        'message' => __('message.payment_captured_wallet_credited'),
                        'data' => [
                            'order_id' => $order['id'],
                            'captured' => true,
                            'credited' => $credited
                        ]
                    ], 200);
                }

                // If immediate capture did not complete (e.g. requires 3D secure authentication)
                return response()->json([
                    'status' => true,
                    'message' => __('message.payment_action_required'),
                    'data' => [
                        'order_id' => $order['id'],
                        'captured' => false,
                        'approval_url' => $order['approval_url']
                    ]
                ], 200);
            }

            // Standard or Advanced Checkout (where client enters card or uses PayPal wallet button)
            $order = $this->paypalService->createOrder($userId, $amount, $currency, $saveCard);
            $clientToken = $this->paypalService->generateClientToken($userId);

            return response()->json([
                'status' => true,
                'message' => __('message.paypal_init_success'),
                'data' => [
                    'order_id' => $order['id'],
                    'client_token' => $clientToken,
                    'approval_url' => $order['approval_url']
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('PayPal createPayment Error: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => __('message.paypal_init_failed'),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Capture PayPal payment after frontend SDK completes approval.
     */
    public function capturePayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => __('message.validation_error'),
                'errors' => $validator->errors()
            ], 422);
        }

        $orderId = $request->order_id;

        try {
            $capture = $this->paypalService->captureOrder($orderId);
            $credited = $this->processCaptureAndCredit($capture, $orderId);

            return response()->json([
                'status' => true,
                'message' => __('message.payment_captured_wallet_credited'),
                'data' => [
                    'order_id' => $orderId,
                    'captured' => true,
                    'credited' => $credited
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('PayPal capturePayment Error: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => __('message.paypal_capture_failed'),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get saved cards for the authenticated user.
     */
    public function getSavedCards(Request $request)
    {
        $userId = auth()->user()->id;
        $cards = UserCard::where('user_id', $userId)
            ->where('gateway', 'paypal')
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'status' => true,
            'data' => $cards
        ], 200);
    }

    /**
     * Delete a saved card.
     */
    public function deleteCard($id)
    {
        $userId = auth()->user()->id;
        $card = UserCard::where('user_id', $userId)->where('gateway', 'paypal')->find($id);

        if (!$card) {
            return response()->json([
                'status' => false,
                'message' => __('message.card_not_found')
            ], 404);
        }

        $card->delete();

        return response()->json([
            'status' => true,
            'message' => __('message.card_deleted')
        ], 200);
    }

    /**
     * Webhook listener for PayPal events.
     */
    public function webhook(Request $request)
    {
        Log::info("PayPal log Received: ");

        $payload = $request->all();
        $eventType = $payload['event_type'] ?? null;

        if (!$eventType) {
            return response()->json(['status' => 'ignored', 'message' => 'No event type'], 200);
        }

        Log::info("PayPal Webhook Received event: {$eventType}");

        if ($eventType === 'CHECKOUT.ORDER.APPROVED') {
            $orderId = $payload['resource']['id'] ?? null;
            if ($orderId) {
                try {
                    $capture = $this->paypalService->captureOrder($orderId);
                    $this->processCaptureAndCredit($capture, $orderId);
                    return response()->json(['status' => 'success', 'message' => 'Payment captured and wallet credited.'], 200);
                } catch (\Exception $e) {
                    Log::error("PayPal Webhook capture error: " . $e->getMessage());
                    return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
                }
            }
        }

        if ($eventType === 'PAYMENT.CAPTURE.COMPLETED') {
            $orderId = $payload['resource']['supplementary_data']['related_ids']['order_id'] ?? null;
            try {
                $this->processCaptureAndCredit($payload['resource'], $orderId);
                return response()->json(['status' => 'success', 'message' => 'Wallet balance credited.'], 200);
            } catch (\Exception $e) {
                Log::error("PayPal Webhook credit error: " . $e->getMessage());
                return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
            }
        }

        return response()->json(['status' => 'ignored', 'message' => 'Event not handled'], 200);
    }

    /**
     * Redirect return page for customer checkout completion.
     */
    public function returnCapture(Request $request)
    {
        $orderId = $request->query('token');

        if (!$orderId) {
            return $this->showPaymentStatusView(false, 'Order reference ID (token) is missing.');
        }

        try {
            $capture = $this->paypalService->captureOrder($orderId);
            $credited = $this->processCaptureAndCredit($capture, $orderId);

            $amount = $capture['purchase_units'][0]['payments']['captures'][0]['amount']['value'] ?? '0.00';
            $currency = $capture['purchase_units'][0]['payments']['captures'][0]['amount']['currency_code'] ?? 'USD';

            $message = $credited 
                ? "Your wallet has been successfully recharged with {$amount} {$currency}!"
                : "Your payment of {$amount} {$currency} has already been verified and processed.";

            return $this->showPaymentStatusView(true, $message);

        } catch (\Exception $e) {
            Log::error("PayPal Return redirect capture error: " . $e->getMessage());
            return $this->showPaymentStatusView(false, $e->getMessage());
        }
    }

    /**
     * Credit wallet helper with double credit protection.
     */
    protected function processCaptureAndCredit($capture, $orderId = null)
    {
        // Mutex lock: only one process (webhook OR app capture call) handles a given order at a time.
        // This prevents race conditions when both fire simultaneously.
        $lockKey = 'paypal_order_lock_' . ($orderId ?? ($capture['id'] ?? uniqid()));
        $lock = Cache::lock($lockKey, 30); // 30-second TTL

        if (!$lock->get()) {
            Log::info("PayPal Order {$orderId}: Another process is already handling this order. Skipping.");
            return false;
        }

        try {
            return $this->doCaptureAndCredit($capture, $orderId);
        } finally {
            $lock->release();
        }
    }

    /**
     * Internal: perform the actual wallet credit. Called inside the mutex lock.
     */
    private function doCaptureAndCredit($capture, $orderId = null)
    {
        $captureId = $capture['id'] ?? null;
        $status = $capture['status'] ?? null;

        if ($status !== 'COMPLETED') {
            Log::warning("PayPal transaction capture status is not COMPLETED: {$status}");
            return false;
        }

        // Retrieve amount and custom_id
        $amount = null;
        $currency = 'USD';
        $userId = null;

        if (isset($capture['purchase_units'][0])) {
            $unit = $capture['purchase_units'][0];
            $userId = $unit['custom_id'] ?? $unit['payments']['captures'][0]['custom_id'] ?? null;
            $amount = $unit['payments']['captures'][0]['amount']['value'] ?? null;
            $currency = $unit['payments']['captures'][0]['amount']['currency_code'] ?? 'USD';
            if (!$captureId) {
                $captureId = $unit['payments']['captures'][0]['id'] ?? null;
            }
        } else {
            // Webhook completed format
            $amount = $capture['amount']['value'] ?? null;
            $currency = $capture['amount']['currency_code'] ?? 'USD';
            $userId = $capture['custom_id'] ?? null;
        }

        if (!$userId || !$amount) {
            throw new \Exception("Missing transaction metadata (User ID: {$userId}, Amount: {$amount})");
        }

        $user = User::withoutGlobalScopes()->find((int) $userId);
        if (!$user) {
            throw new \Exception("User ID {$userId} not found in database. Users in DB: " . User::withoutGlobalScopes()->count());
        }

        // Save vaulted card details if present in the response
        try {
            if (isset($capture['payment_source']['card']['attributes']['vault']['id'])) {
                $vaultData = $capture['payment_source']['card']['attributes']['vault'];
                $cardData = $capture['payment_source']['card'];

                $existingCard = UserCard::where('user_id', $user->id)
                    ->where('vault_id', $vaultData['id'])
                    ->first();

                if (!$existingCard) {
                    UserCard::create([
                        'user_id' => $user->id,
                        'gateway' => 'paypal',
                        'vault_id' => $vaultData['id'],
                        'card_brand' => $cardData['brand'] ?? null,
                        'last_digits' => $cardData['last_digits'] ?? null,
                        'expiry' => $cardData['expiry'] ?? null,
                        'is_default' => !UserCard::where('user_id', $user->id)->exists()
                    ]);
                    Log::info("PayPal Card vaulted successfully for User ID {$user->id}");
                }
            }
        } catch (\Exception $cardEx) {
            Log::error("Failed to save vaulted card details: " . $cardEx->getMessage());
        }

        // Idempotency: Double Crediting check
        $reference = $captureId ?? $orderId;
        $existingHistory = WalletHistory::where('user_id', $user->id)
            ->where(function ($query) use ($reference, $orderId, $captureId) {
                if ($reference) {
                    $query->where('description', 'like', "%{$reference}%");
                }
                if ($orderId) {
                    $query->orWhere('description', 'like', "%{$orderId}%");
                }
                if ($captureId) {
                    $query->orWhere('description', 'like', "%{$captureId}%");
                }
            })->first();

        if ($existingHistory) {
            Log::info("Idempotency match: Order {$orderId} / Capture {$captureId} has already been credited to user {$userId}.");
            return false;
        }

        // Credit Wallet
        DB::transaction(function () use ($user, $amount, $currency, $orderId, $captureId, $capture) {
            $wallet = Wallet::firstOrCreate(
                ['user_id' => $user->id],
                ['total_amount' => 0, 'currency' => $currency]
            );

            $newBalance = $wallet->total_amount + (float)$amount;
            $wallet->total_amount = $newBalance;
            $wallet->save();

            $history = WalletHistory::create([
                'user_id' => $user->id,
                'type' => 'credit',
                'transaction_type' => 'wallet_recharge',
                'currency' => $currency,
                'amount' => (float)$amount,
                'balance' => $newBalance,
                'datetime' => now(),
                'description' => "Paypal Credit - Order ID: {$orderId}, Capture ID: {$captureId}",
                'data' => $capture
            ]);

            // Fire real-time notification
            try {
                $notificationData = [
                    'id' => $history->id,
                    'type' => 'wallet_recharge',
                    'subject' => 'Wallet Recharge Successful',
                    'message' => __('message.wallet_credited_paypal', ['amount' => $amount, 'currency' => $currency]),
                ];
                $user->notify(new CommonNotification($notificationData['type'], $notificationData));
            } catch (\Exception $e) {
                Log::error("Failed to notify user of PayPal credit: " . $e->getMessage());
            }
        });

        return true;
    }

    /**
     * Glassmorphism dark-theme Outfit font success/error views.
     */
    protected function showPaymentStatusView($success, $message)
    {
        $statusTitle = $success ? 'Recharge Successful!' : 'Recharge Failed';
        $statusSubtitle = $success ? 'Success' : 'Error';
        $themeColor = $success ? '#10B981' : '#EF4444';
        $statusIcon = $success 
            ? '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="success-icon"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>'
            : '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="error-icon"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>';

        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$statusTitle}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Outfit', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
            color: #f8fafc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            overflow: hidden;
            position: relative;
        }
        body::before {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(99, 102, 241, 0.15) 0%, transparent 70%);
            top: 10%;
            left: 10%;
            pointer-events: none;
        }
        body::after {
            content: '';
            position: absolute;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(16, 185, 129, 0.1) 0%, transparent 70%);
            bottom: 10%;
            right: 10%;
            pointer-events: none;
        }
        .card {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 24px;
            width: 100%;
            max-width: 440px;
            padding: 40px 30px;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            transform: translateY(0);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            animation: fadeIn 0.8s ease-out;
        }
        .card:hover {
            transform: translateY(-5px);
            border-color: rgba(255, 255, 255, 0.15);
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.4);
        }
        .icon-wrapper {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.03);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            position: relative;
        }
        .icon-wrapper::after {
            content: '';
            position: absolute;
            inset: -4px;
            border-radius: 50%;
            border: 2px dashed {$themeColor};
            opacity: 0.3;
            animation: spin 15s linear infinite;
        }
        .success-icon {
            width: 36px;
            height: 36px;
            color: #10B981;
        }
        .error-icon {
            width: 36px;
            height: 36px;
            color: #EF4444;
        }
        .badge {
            display: inline-block;
            padding: 6px 14px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-weight: 600;
            color: {$themeColor};
            margin-bottom: 20px;
        }
        h1 {
            font-size: 1.8rem;
            font-weight: 800;
            margin-bottom: 12px;
            letter-spacing: -0.5px;
            background: linear-gradient(to right, #ffffff, #cbd5e1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        p {
            font-size: 0.95rem;
            color: #94a3b8;
            line-height: 1.6;
            margin-bottom: 35px;
            font-weight: 300;
        }
        .btn {
            display: block;
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            border: none;
            border-radius: 14px;
            color: white;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
            text-decoration: none;
        }
        .btn:hover {
            background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(99, 102, 241, 0.4);
        }
        .btn:active {
            transform: translateY(0);
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes spin {
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon-wrapper">
            {$statusIcon}
        </div>
        <span class="badge">{$statusSubtitle}</span>
        <h1>{$statusTitle}</h1>
        <p>{$message}</p>
        <a href="javascript:window.close();" class="btn">Close Window</a>
    </div>
</body>
</html>
HTML;

        return response($html, 200)->header('Content-Type', 'text/html');
    }
}
