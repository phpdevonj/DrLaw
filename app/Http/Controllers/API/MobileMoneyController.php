<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Repositories\MobileMoneyRepository;
use App\Services\CashInService;
use App\Services\CashOutService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MobileMoneyController extends Controller
{
    protected $mobileMoneyRepository;
    protected $cashInService;
    protected $cashOutService;

    public function __construct(
        MobileMoneyRepository $mobileMoneyRepository,
        CashInService $cashInService,
        CashOutService $cashOutService
    ) {
        $this->mobileMoneyRepository = $mobileMoneyRepository;
        $this->cashInService         = $cashInService;
        $this->cashOutService        = $cashOutService;
    }

    public function mobileMoneyWebhook(Request $request)
    {
        try {
            $payload = $request->all();

            // Resolve the record to determine which service handles this webhook
            $referenceId = !empty($payload['reference']) ? $payload['reference'] : (!empty($payload['trid']) ? $payload['trid'] : null);
            
            $record      = $referenceId
                ? $this->mobileMoneyRepository->findByReference($referenceId)
                : null;

            $type = $record->type ?? 'cashin'; // default to cashin for legacy records

            if ($type === 'cashout') {
                $this->cashOutService->handleWebhook($payload);
            } else {
                $this->cashInService->handleWebhook($payload);
            }

            return response()->json(['status' => 'received'], 200);
        } catch (\Exception $e) {
            Log::channel('mobile_money')->error('Mobile Money Webhook processing failed', [
                'payload' => $request->all(),
                'error'   => $e->getMessage(),
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Webhook processing failed',
            ], 400);
        }
    }
}
