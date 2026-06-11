<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PaymentGateway;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class StripeCardController extends Controller
{
    protected $stripeSecret;

    public function __construct()
    {
        $paymentGateway = PaymentGateway::where('type', 'stripe')->where('status', 1)->first();
        if ($paymentGateway) {
            $this->stripeSecret = $paymentGateway->is_test ? ($paymentGateway->test_value['secret_key'] ?? null) : ($paymentGateway->live_value['secret_key'] ?? null);
        }
    }

    protected function checkStripeConfigured()
    {
        if (empty($this->stripeSecret)) {
            return response()->json(['message' => 'Stripe is not configured.'], 503);
        }
        return null;
    }

    // 1. Create SetupIntent to add a card
    public function createSetupIntent(Request $request)
    {
        if ($guard = $this->checkStripeConfigured()) return $guard;

        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|string',
        ]);
    
        if ($validator->fails()) {
             return response()->json([
                 'message' => 'Validation failed',
                 'errors' => $validator->errors()
             ], 422);
        }
        
        $response = Http::withToken($this->stripeSecret)->asForm()->post('https://api.stripe.com/v1/setup_intents', [
            'customer' => $request->customer_id,
        ]);

        return response()->json($response->json(), $response->status());
    }

    // 2. List all saved cards
    public function listPaymentMethods(Request $request)
    {
        if ($guard = $this->checkStripeConfigured()) return $guard;

        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|string',
        ]);
    
        if ($validator->fails()) {
             return response()->json([
                 'message' => 'Validation failed',
                 'errors' => $validator->errors()
             ], 422);
        }
    
        // Fetch customer details to get default payment method
        $customerResponse = Http::withToken($this->stripeSecret)
            ->get("https://api.stripe.com/v1/customers/{$request->customer_id}");
    
        if ($customerResponse->failed()) {
            return response()->json([
                'status' => false,
                'message' => 'Unable to fetch customer details',
                'error' => $customerResponse->json(),
            ], $customerResponse->status());
        }
    
        $defaultPaymentMethod = $customerResponse['invoice_settings']['default_payment_method'] ?? null;
    
        // Fetch all card payment methods
        $paymentMethodsResponse = Http::withToken($this->stripeSecret)
            ->get('https://api.stripe.com/v1/payment_methods', [
                'customer' => $request->customer_id,
                'type' => 'card',
            ]);
    
        if ($paymentMethodsResponse->failed()) {
            return response()->json([
                'status' => false,
                'message' => 'Unable to fetch payment methods',
                'error' => $paymentMethodsResponse->json(),
            ], $paymentMethodsResponse->status());
        }
    
        $cards = $paymentMethodsResponse['data'];
    
        // Add is_default flag
        foreach ($cards as &$card) {
            $card['is_default'] = $card['id'] === $defaultPaymentMethod;
        }
    
        return response()->json([
            'status' => true,
            'message' => 'Payment methods fetched successfully',
            'data' => $cards,
        ]);
    }

    // 3. Delete a card
    public function deletePaymentMethod(Request $request)
    {
        if ($guard = $this->checkStripeConfigured()) return $guard;

        $validator = Validator::make($request->all(), [
            'payment_method_id' => 'required|string',
        ]);
    
        if ($validator->fails()) {
             return response()->json([
                 'message' => 'Validation failed',
                 'errors' => $validator->errors()
             ], 422);
        }

        $response = Http::withToken($this->stripeSecret)->asForm()->post("https://api.stripe.com/v1/payment_methods/{$request->payment_method_id}/detach");

        return response()->json($response->json(), $response->status());
    }

    // 4. Set default card
    public function setDefaultCard(Request $request)
    {
        if ($guard = $this->checkStripeConfigured()) return $guard;

        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|string',
            'payment_method_id' => 'required|string',
        ]);
    
        if ($validator->fails()) {
             return response()->json([
                 'message' => 'Validation failed',
                 'errors' => $validator->errors()
             ], 422);
        }

        $response = Http::withToken($this->stripeSecret)->asForm()->post("https://api.stripe.com/v1/customers/{$request->customer_id}", [
            'invoice_settings[default_payment_method]' => $request->payment_method_id,
        ]);

        return response()->json($response->json(), $response->status());
    }

    // 5. Create PaymentIntent
    public function createPaymentIntent(Request $request)
    {
        if ($guard = $this->checkStripeConfigured()) return $guard;

        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|string',
            'amount' => 'required',
            'currency' => 'required',
        ]);
    
        if ($validator->fails()) {
             return response()->json([
                 'message' => 'Validation failed',
                 'errors' => $validator->errors()
             ], 422);
        }

        // Get default payment method
        $customerResponse = Http::withToken($this->stripeSecret)
            ->get("https://api.stripe.com/v1/customers/{$request->customer_id}");

        if (!$customerResponse->successful()) {
            return response()->json(['error' => 'Unable to retrieve customer.'], 500);
        }

        $customer = $customerResponse->json();
        $defaultPaymentMethod = $customer['invoice_settings']['default_payment_method'] ?? null;

        if (!$defaultPaymentMethod) {
            return response()->json(['error' => 'No default payment method set.'], 422);
        }

        // Create PaymentIntent
        $paymentIntent = Http::withToken($this->stripeSecret)->asForm()->post('https://api.stripe.com/v1/payment_intents', [
            'amount' => (int) $request->amount,
            'currency' => $request->currency,
            'customer' => $request->customer_id,
            'payment_method' => $defaultPaymentMethod,
            'payment_method_types[]' => 'card',
            'confirmation_method' => 'automatic',
            'confirm' => 'true',
            'capture_method' => 'manual',
        ]);

        return response()->json($paymentIntent->json(), $paymentIntent->status());
    }

    // 6. Capture PaymentIntent
    public function capturePaymentIntent(Request $request)
    {
        if ($guard = $this->checkStripeConfigured()) return $guard;

        $validator = Validator::make($request->all(), [
            'payment_intent_id' => 'required|string',
            'amount' => 'nullable',
        ]);
    
        if ($validator->fails()) {
             return response()->json([
                 'message' => 'Validation failed',
                 'errors' => $validator->errors()
             ], 422);
        }

        $payload = [];
        if ($request->has('amount')) {
            $payload['amount_to_capture'] = (int) $request->amount;
        }

        $response = Http::withToken($this->stripeSecret)->asForm()->post("https://api.stripe.com/v1/payment_intents/{$request->payment_intent_id}/capture", $payload);

        if ($response->successful()) {
            $result = $response->json();
        
            if (isset($result['status']) && $result['status'] === 'succeeded') {
                return response()->json($response->json(), $response->status());
            }
        
            return json_message_response([
                'message' => 'Payment intent was not successful',
                'status' => $result['status'] ?? 'unknown',
            ], 400);
        } else {
            $error = $response->json();

            if (isset($error['error']['payment_intent']['status']) && $error['error']['payment_intent']['status'] === 'succeeded') {
                $error_response['error'] = [
                    'message' => $error['error']['message'] ?? 'Stripe capture failed',
                    'code' => $error['error']['code'] ?? 'unknown',
                    'payment_intent' => $error['error']['payment_intent'],
                ];
                return response()->json($error_response, 200);
            }else{
                $error_response['error'] = [
                    'message' => $error['error']['message'] ?? 'Stripe capture failed',
                    'type' => $error['error']['type'] ?? 'unknown',
                    'payment_intent' => $error['error']['payment_intent'],
                ];
                return response()->json($error_response, $response->status());
            }            
        }
    }

    // 7. Cancel PaymentIntent
    public function cancelPaymentIntent(Request $request)
    {
        if ($guard = $this->checkStripeConfigured()) return $guard;

        $validator = Validator::make($request->all(), [
            'payment_intent_id' => 'required|string',
        ]);
    
        if ($validator->fails()) {
             return response()->json([
                 'message' => 'Validation failed',
                 'errors' => $validator->errors()
             ], 422);
        }

        $response = Http::withToken($this->stripeSecret)->asForm()
            ->post("https://api.stripe.com/v1/payment_intents/{$request->payment_intent_id}/cancel");

        if ($response->successful()) {
            $result = $response->json();
        
            if (isset($result['status']) && $result['status'] === 'canceled') {
                return response()->json($response->json(), $response->status());
            }
        
            return json_message_response([
                'message' => 'Payment intent was not successful',
                'status' => $result['status'] ?? 'unknown',
            ], 400);
        } else {
            $error = $response->json();

            if (isset($error['error']['payment_intent']['status']) && $error['error']['payment_intent']['status'] === 'canceled') {
                $error_response['error'] = [
                    'message' => $error['error']['message'] ?? 'Stripe cancellation failed',
                    'code' => $error['error']['code'] ?? 'unknown',
                    'payment_intent' => $error['error']['payment_intent'],
                ];
                return response()->json($error_response, 200);
            }else{
                $error_response['error'] = [
                    'message' => $error['error']['message'] ?? 'Stripe cancellation failed',
                    'type' => $error['error']['type'] ?? 'unknown',
                    'payment_intent' => $error['error']['payment_intent'],
                ];
                return response()->json($error_response, $response->status());
            }            
        }
    }

    public function getTransactionsList(Request $request){
        if ($guard = $this->checkStripeConfigured()) return $guard;

        $admin_timezone = User::admin()->timezone;
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|string',
            'per_page'    => 'nullable|integer',
        ]);
    
        if ($validator->fails()) {
            return json_custom_response([
                'message' => 'Validation failed',
                'errors'  => $validator->errors()
            ], 422);
        }
    
        try {
            // Fetch max 100 transactions from Stripe
            $response = Http::withToken($this->stripeSecret)
                ->get('https://api.stripe.com/v1/charges', [
                    'customer' => $request->customer_id,
                    'limit'    => 100
                ]);
    
            if (!$response->successful()) {
                throw new \Exception('Failed to fetch transactions from Stripe.');
            }
    
            $charges = $response->json()['data'];
    
            // Convert Stripe data to collection
            $transactions = collect($charges)->map(function ($charge) use($admin_timezone) {
                $card = $charge['payment_method_details']['card'] ?? [];
    
                return [
                    'transaction_id'    => $charge['id'],
                    'status'            => $charge['status'],
                    'created_at'        => Carbon::createFromTimestamp($charge['created'], 'UTC')->setTimezone($admin_timezone)->format('Y-m-d H:i:s'),
                    'currency'          => strtoupper($charge['currency']),
                    'amount_authorized' => $charge['amount'] / 100,
                    'amount_captured'   => $charge['amount_captured'] / 100,
                    'amount_refunded'   => $charge['amount_refunded'] / 100,
                    'card_brand'        => $card['brand'] ?? null,
                    'card_last4'        => $card['last4'] ?? null,
                    'card_exp_month'    => $card['exp_month'] ?? null,
                    'card_exp_year'     => $card['exp_year'] ?? null,
                    'card_expiry'       => ($card['exp_month'] && $card['exp_year'])
                        ? str_pad($card['exp_month'], 2, '0', STR_PAD_LEFT) . '/' . $card['exp_year']
                        : null,
                    'card_country'      => $card['country'] ?? null,
                ];
            });
    
            // Handle per_page
            $per_page = config('constant.PER_PAGE_LIMIT', 10);
            if ($request->has('per_page') && !empty($request->per_page)) {
                if (is_numeric($request->per_page)) {
                    $per_page = $request->per_page;
                }
                if ($request->per_page == -1) {
                    $per_page = $transactions->count();
                }
            }
    
            $page     = $request->input('page', 1);
            $offset   = ($page - 1) * $per_page;
            $paginated = new LengthAwarePaginator(
                $transactions->slice($offset, $per_page)->values(),
                $transactions->count(),
                $per_page,
                $page,
                ['path' => url()->current()]
            );
    
            // Optionally use resource collection like: TransactionResource::collection($paginated)
            $items = $paginated;
    
            $response = [
                'pagination' => json_pagination_response($items),
                'data'       => $items,
            ];
    
            return json_custom_response($response);
    
        } catch (\Exception $e) {
            return json_custom_response([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        } 
    }

    // create customer on stripe
    public function createStripeCustomer(Request $request)
    {
        if ($guard = $this->checkStripeConfigured()) return $guard;

        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return json_message_response($validator->errors(), 400);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return json_message_response('User not found.', 400);
        }

        if (!empty($user->stripe_customer_id)) {
            $response_data = [
                'message' => 'Customer already created on stripe.',                
                'stripe_customer_id' => $user->stripe_customer_id,                
            ];
            return json_custom_response($response_data, 200);
        }

        $stripeCustomer = createStripeCustomer(
            $user->email,
            $user->display_name,
            $user->contact_number,
            'stripe'
        );

        if (!isset($stripeCustomer['id'])) {
            return json_message_response('Failed to create Stripe customer.', 400);
        }

        $user->stripe_customer_id = $stripeCustomer['id'];
        $user->save();

        return json_custom_response([
            'message' => 'Customer created on stripe',
            'stripe_customer_id' => $stripeCustomer['id'],                
        ]);
    }

}
