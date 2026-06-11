<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CashOutService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * CashOutController – Driver-facing endpoints for mobile-money cash-out (withdrawals).
 *
 * Routes (all under /api/mobile-money/cashout):
 *   POST initiate – start a withdrawal / payout
 *   GET  verify   – poll / verify payout status
 */
class CashOutController extends Controller
{
    protected $cashOutService;

    public function __construct(CashOutService $cashOutService)
    {
        $this->cashOutService = $cashOutService;
    }

    public function initiate(Request $request)
    {
        try {
            $transaction = $this->cashOutService->initiatePayment($request->user()->id, $request->all());
            return response()->json([
                'status'  => 'success',
                'message' => 'Cash-out initiated. Payout is being processed.',
                'data'    => [
                    'transaction_id' => $transaction->reference_id,
                    'status'         => $transaction->status,
                    'type'           => $transaction->type,
                ],
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            Log::error('CashOut initiate error', ['message' => $e->getMessage()]);
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function verify(Request $request)
    {
        try {
            $trid = $request->query('trid');
            if (!$trid) throw new \Exception('Transaction reference (trid) is required.');

            $transaction = $this->cashOutService->verifyTransaction($trid);
            return response()->json([
                'status' => 'success',
                'data'   => [
                    'transaction_id' => $transaction->reference_id,
                    'status'         => $transaction->status,
                    'amount'         => $transaction->amount,
                    'type'           => $transaction->type,
                    'message'        => $transaction->response_message,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
