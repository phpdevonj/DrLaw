<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PaymentGateway;
use App\Http\Resources\PaymentGatewayResource;

class PaymentGatewayController extends Controller
{

    public function getList(Request $request)
    {
        // Only return gateways whose type is active (uncommented) in constant.php
        $activeGatewayTypes = array_keys(config('constant.PAYMENT_GATEWAY_SETTING', []));

        $gateways = PaymentGateway::where('status', 1)
            ->where('type', '!=', 'cash')
            ->whereIn('type', $activeGatewayTypes)
            ->orderBy('title', 'asc')
            ->paginate(10);
        $items = PaymentGatewayResource::collection($gateways);

        $response = [
            'pagination' => json_pagination_response($items),
            'data' => $items,
        ];
        
        return json_custom_response($response);
    }
}
