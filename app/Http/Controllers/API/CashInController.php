<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CashInService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * CashInController – Rider-facing endpoints for mobile-money cash-in (top-up).
 *
 * Routes (all under /api/mobile-money/cashin):
 *   GET  services   – list available payment services
 *   GET  details    – get details for a specific service
 *   POST quote      – get a payment quote
 *   POST collect    – initiate the cash-in
 *   GET  verify     – poll / verify transaction status
 */
class CashInController extends Controller
{
    protected $cashInService;

    public function __construct(CashInService $cashInService)
    {
        $this->cashInService = $cashInService;
    }

    public function getServices(Request $request)
    {
        try {
            $services = $this->cashInService->getServices($request->all());
            return response()->json(['status' => 'success', 'data' => $services]);
        } catch (\Exception $e) {
            Log::error('CashIn getServices error', ['message' => $e->getMessage()]);
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function getServiceDetails(Request $request)
    {
        try {
            $serviceId = $request->query('serviceId');
            if (!$serviceId) throw new \Exception('Service ID is required.');

            $details = $this->cashInService->getServiceDetails($serviceId);
            return response()->json(['status' => 'success', 'data' => $details]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function getQuote(Request $request)
    {
        try {
            $quote = $this->cashInService->getQuote($request->all());
            return response()->json(['status' => 'success', 'data' => $quote]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function collectPayment(Request $request)
    {
        try {
            $transaction = $this->cashInService->initiatePayment($request->user()->id, $request->all());
            return response()->json([
                'status'  => 'success',
                'message' => 'Cash-in initiated. Please confirm payment on your phone.',
                'data'    => [
                    'transaction_id' => $transaction->reference_id,
                    'status'         => $transaction->status,
                    'type'           => $transaction->type,
                ],
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            Log::error('CashIn collectPayment error', ['message' => $e->getMessage()]);
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function verifyTransaction(Request $request)
    {
        try {
            $trid = $request->query('trid');
            if (!$trid) throw new \Exception('Transaction reference (trid) is required.');

            $transaction = $this->cashInService->verifyTransaction($trid);
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
