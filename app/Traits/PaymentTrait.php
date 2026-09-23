<?php

namespace App\Traits;
use Illuminate\Http\Request;
use App\Models\RideRequest;
use App\Models\User;
use App\Models\Payment;
use App\Models\Wallet;
use App\Models\WalletHistory;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;

trait PaymentTrait {
    
    public function walletTransaction($ride_request_id) {
        $ride_request = RideRequest::where('id', $ride_request_id)->first();
        
        if( $ride_request == null ) {
            return false;
        }

        $admin_id = User::admin()->id;
        $payment = $ride_request->payment;

        $commission_type = $ride_request->service->commission_type ?? 0;        
        $company_fee_charge = $ride_request->company_fee_charge ?? 0; // Company Fee
        $expenses_charge    = $ride_request->expenses_charge ?? 0; // Expenses

        $admin_commission = $ride_request->service->admin_commission ?? 0;
        $admin_commission = $admin_commission + $company_fee_charge + $expenses_charge;
        $coupon_discount  = $ride_request->coupon_discount ?? 0;

        $fleet_id = optional($ride_request->driver)->fleet_id;
        
        $fleet_commission = 0;
        if( $fleet_id != null ) {
            $fleet_commission = $ride_request->service->fleet_commission ?? 0;
        }
        $ride_request_amount = $payment->total_amount - $ride_request->extra_charges_amount;
      
        if( $payment->payment_type == 'cash') {
            $payment->received_by = 'driver';
        } elseif ($payment->payment_type == 'wallet') {
            $payment->received_by = 'wallet';
        } else {
            $payment->received_by = 'admin';
        }
        $driver_tips = $ride_request->tips ?? 0;
        $payment->admin_commission = $admin_commission - $coupon_discount; // less coupon discount amount form admin commission
        $payment->fleet_commission = $fleet_commission;
        $driver_fee = $ride_request_amount - $admin_commission - $fleet_commission + $coupon_discount;
        $payment->driver_fee = (float) number_format( (float) $driver_fee, 2,'.','');
        $payment->driver_tips = $driver_tips;
        $driver_commission = $driver_fee + $driver_tips + $ride_request->extra_charges_amount;
        $payment->driver_commission = (float) number_format( (float) $driver_commission, 2,'.','');
        $payment->company_fee_charge = (float) number_format( (float) $company_fee_charge, 2,'.','');
        $payment->expenses_charge = (float) number_format( (float) $expenses_charge, 2,'.','');
        $payment->save();
        
        // $currency = optional($ride_request->service) && optional($ride_request->service)->region ? optional($ride_request->service)->region->currency_code : null;

        $currency_code = SettingData('CURRENCY', 'CURRENCY_CODE') ?? 'USD';
        $currency_data = currencyArray($currency_code);
        $currency = strtolower($currency_data['code']);
        try {
            DB::beginTransaction();
            if( $payment->payment_type == 'wallet' || $payment->payment_type == 'card')
            {
                $driver_wallet = Wallet::firstOrCreate(
                    [ 'user_id' => $ride_request->driver_id ]
                );
                $driver_wallet->total_amount += $payment->driver_commission;
                $driver_wallet->save();

                $driver_wallet_history = [
                    'user_id'           => $ride_request->driver_id,
                    'type'              => 'credit',
                    'transaction_type'  => 'ride_fee',
                    'currency'          => $currency,
                    'amount'            => $payment->driver_commission,
                    'balance'           => $driver_wallet->total_amount,
                    'ride_request_id'   => $payment->ride_request_id,
                    'datetime'          => date('Y-m-d H:i:s'),
                    'data' => [
                        'payment_id'    => $payment->id,
                        'tips'          => $payment->driver_tips
                    ]
                ];
                WalletHistory::create($driver_wallet_history);

                $admin_wallet = Wallet::firstOrCreate(
                    [ 'user_id' => $admin_id ]
                );
                
                $admin_wallet->total_amount = $admin_wallet->total_amount + $admin_commission;
                $admin_wallet->save();

                $type = $admin_commission >= 0 ? 'credit' : 'debit';

                $admin_wallet_history = [ 
                    'user_id'           => $admin_id,
                    'type'              => $type,
                    'transaction_type'  => 'admin_commission',
                    'currency'          => $currency,
                    'amount'            => $admin_commission,
                    'balance'           => $admin_wallet->total_amount,
                    'ride_request_id'   => $payment->ride_request_id,
                    'datetime'          => date('Y-m-d H:i:s'),
                    'data' => [
                        'payment_id'    => $payment->id
                    ]
                ];
                WalletHistory::create($admin_wallet_history);

                if( $fleet_id != null ) {
                    $fleet_wallet = Wallet::firstOrCreate(
                        [ 'user_id' => $fleet_id ]
                    );
                    $fleet_wallet->total_amount = $fleet_wallet->total_amount + $fleet_commission;
                    $fleet_wallet->save();

                    $fleet_wallet_history = [ 
                        'user_id'           => $fleet_id,
                        'type'              => 'credit',
                        'transaction_type'  => 'fleet_commision',
                        'currency'          => $currency,
                        'amount'            => $fleet_commission,
                        'balance'           => $fleet_wallet->total_amount,
                        'ride_request_id'   => $payment->ride_request_id,
                        'datetime'          => date('Y-m-d H:i:s'),
                        'data' => [
                            'payment_id' => $payment->id
                        ]
                    ];

                    WalletHistory::create($fleet_wallet_history);
                }
            }elseif ($payment->payment_type == 'cash') {
            /*
                $driver_wallet = Wallet::firstOrCreate(
                    [ 'user_id' => $ride_request->driver_id ]
                );
                $driver_wallet->collected_cash += $payment->total_amount;
                $driver_wallet->total_amount = $payment->driver_fee + $payment->driver_tips;
                $driver_wallet->save();

                $driver_wallet_history = [
                    'user_id'           => $ride_request->driver_id,
                    'type'              => 'credit',
                    'transaction_type'  => 'ride_fee',
                    'currency'          => $currency,
                    'amount'            => $payment->driver_fee,
                    'balance'           => $driver_wallet->total_amount,
                    'ride_request_id'   => $payment->ride_request_id,
                    'data' => [
                        'payment_id'    => $payment->id,
                        'tips'          => $payment->driver_tips,                    
                    ]
                ];
                WalletHistory::create($driver_wallet_history);
            */
                $admin_wallet = Wallet::firstOrCreate(
                    [ 'user_id' => $admin_id ]
                );
                $admin_wallet->total_amount = $admin_wallet->total_amount + $admin_commission;
                $admin_wallet->save();

                $admin_wallet_history = [ 
                    'user_id'           => $admin_id,
                    'type'              => 'credit',
                    'transaction_type'  => 'admin_commission',
                    'currency'          => $currency,
                    'amount'            => $admin_commission,
                    'balance'           => $admin_wallet->total_amount,
                    'ride_request_id'   => $payment->ride_request_id,
                    'datetime'          => date('Y-m-d H:i:s'),
                    'data' => [
                        'payment_id'    => $payment->id
                    ]
                ];
                WalletHistory::create($admin_wallet_history);

                $driver_wallet = Wallet::firstOrCreate(
                    [ 'user_id' => $ride_request->driver_id ]
                );
                
                if ($payment->payment_type == 'cash') {
                    $admin_commission = $admin_commission - $coupon_discount;
                } else {
                    $admin_commission = $admin_commission; // Can be omitted, but kept for clarity
                }

                // 1. Deduct from wallet (Subtracting a negative will automatically add to the wallet)
                $driver_wallet->total_amount -= $admin_commission;
                $driver_wallet->save();

                // 2. Determine transaction type and normalize the amount for history logs
                if ($admin_commission < 0) {
                    $transaction_type = 'credit';
                    $log_amount = abs($admin_commission); // Converts the negative value to positive (e.g., -50 becomes 50)
                } else {
                    $transaction_type = 'debit';
                    $log_amount = $admin_commission;
                }

                // 3. Save to history
                $driver_wallet_history = [
                    'user_id'           => $ride_request->driver_id,
                    'type'              => $transaction_type, // Dynamic: 'credit' or 'debit'
                    'transaction_type'  => 'correction',
                    'currency'          => $currency,
                    'amount'            => $log_amount,        // Always stored as a positive representation
                    'balance'           => $driver_wallet->total_amount,
                    'ride_request_id'   => $payment->ride_request_id,
                    'datetime'          => date('Y-m-d H:i:s'),
                ];
                WalletHistory::create($driver_wallet_history);

                if( $fleet_id != null ) {
                    $fleet_wallet = Wallet::firstOrCreate(
                        [ 'user_id' => $fleet_id ]
                    );
                    $fleet_wallet->total_amount = $fleet_wallet->total_amount + $fleet_commission;
                    $fleet_wallet->save();

                    $fleet_wallet_history = [ 
                        'user_id'           => $fleet_id,
                        'type'              => 'credit',
                        'transaction_type'  => 'fleet_commision',
                        'currency'          => $currency,
                        'amount'            => $fleet_commission,
                        'balance'           => $fleet_wallet->total_amount,
                        'ride_request_id'   => $payment->ride_request_id,
                        'datetime'          => date('Y-m-d H:i:s'),
                        'data' => [
                            'payment_id' => $payment->id
                        ]
                    ];
                    WalletHistory::create($fleet_wallet_history);

                
                    $driver_wallet = Wallet::firstOrCreate(
                        [ 'user_id' => $ride_request->driver_id ]
                    );
                    $driver_wallet->total_amount -= $fleet_commission;
                    $driver_wallet->save();
        
                    $driver_wallet_history = [
                        'user_id'           => $ride_request->driver_id,
                        'type'              => 'debit',
                        'transaction_type'  => 'correction',
                        'currency'          => $currency,
                        'amount'            => $fleet_commission,
                        'balance'           => $driver_wallet->total_amount,
                        'ride_request_id'   => $payment->ride_request_id,
                        'datetime'          => date('Y-m-d H:i:s'),
                    ];
                    WalletHistory::create($driver_wallet_history);
                
                }
            }else {
                // $driver_wallet = Wallet::firstOrCreate(
                //     [ 'user_id' => $ride_request->driver_id ]
                // );
                // $driver_wallet->total_amount += $payment->driver_commission;
                // $driver_wallet->save();

                // $driver_wallet_history = [
                //     'user_id'           => $ride_request->driver_id,
                //     'type'              => 'credit',
                //     'transaction_type'  => 'ride_fee',
                //     'currency'          => $currency,
                //     'amount'            => $payment->driver_commission,
                //     'balance'           => $driver_wallet->total_amount,
                //     'ride_request_id'   => $payment->ride_request_id,
                //     'datetime'          => date('Y-m-d H:i:s'),
                //     'data' => [
                //         'payment_id'    => $payment->id,
                //         'tips'          => $payment->driver_tips,                    
                //     ]
                // ];
                // WalletHistory::create($driver_wallet_history);
            /*
                $admin_wallet = Wallet::firstOrCreate(
                    [ 'user_id' => $admin_id ]
                );

                $admin_wallet->online_received += $payment->total_amount;
                $admin_wallet->total_amount += $admin_commission;
                $admin_wallet->save();

                $admin_wallet_history = [ 
                    'user_id'           => $admin_id,
                    'type'              => 'credit',
                    'transaction_type'  => 'admin_commission',
                    'currency'          => $currency,
                    'amount'            => $admin_commission,
                    'balance'           => $admin_wallet->total_amount,
                    'ride_request_id'   => $payment->ride_request_id,
                    'data' => [
                        'payment_id'    => $payment->id
                    ]
                ];
                WalletHistory::create($admin_wallet_history);
            */
                // $admin_wallet = Wallet::firstOrCreate(
                //     [ 'user_id' => $admin_id ]
                // );
                // $admin_wallet->total_amount -= $payment->driver_commission;
                // $admin_wallet->save();

                // $admin_wallet_history = [
                //     'user_id'           => $ride_request->driver_id,
                //     'type'              => 'debit',
                //     'transaction_type'  => 'correction',
                //     'currency'          => $currency,
                //     'amount'            => $payment->driver_commission,
                //     'balance'           => $admin_wallet->total_amount,
                //     'ride_request_id'   => $payment->ride_request_id,
                //     'datetime'          => date('Y-m-d H:i:s'),
                // ];
                // WalletHistory::create($admin_wallet_history);

                // if( $fleet_id != null ) {
                //     $fleet_wallet = Wallet::firstOrCreate(
                //         [ 'user_id' => $fleet_id ]
                //     );
                //     $fleet_wallet->total_amount = $fleet_wallet->total_amount + $fleet_commission;
                //     $fleet_wallet->save();

                //     $fleet_wallet_history = [ 
                //         'user_id'           => $fleet_id,
                //         'type'              => 'credit',
                //         'transaction_type'  => 'fleet_commision',
                //         'currency'          => $currency,
                //         'amount'            => $fleet_commission,
                //         'balance'           => $fleet_wallet->total_amount,
                //         'ride_request_id'   => $payment->ride_request_id,
                //         'datetime'          => date('Y-m-d H:i:s'),
                //         'data' => [
                //             'payment_id' => $payment->id
                //         ]
                //     ];
                //     WalletHistory::create($fleet_wallet_history);

                //     $admin_wallet = Wallet::firstOrCreate(
                //         [ 'user_id' => $admin_id ]
                //     );
                //     $admin_wallet->total_amount -= $fleet_commission;
                //     $admin_wallet->save();
        
                //     $admin_wallet_history = [
                //         'user_id'           => $ride_request->driver_id,
                //         'type'              => 'debit',
                //         'transaction_type'  => 'correction',
                //         'currency'          => $currency,
                //         'amount'            => $fleet_commission,
                //         'balance'           => $admin_wallet->total_amount,
                //         'ride_request_id'   => $payment->ride_request_id,
                //         'datetime'          => date('Y-m-d H:i:s'),
                //     ];
                //     WalletHistory::create($admin_wallet_history);
                // }
            }
            DB::commit();
        } catch(\Exception $e) {
            // \Log::error($e->getMessage());
            DB::rollBack();
            return json_custom_response($e);
        }
        
        return true;
    }
}