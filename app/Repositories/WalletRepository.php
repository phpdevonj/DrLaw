<?php

namespace App\Repositories;

use App\Models\Wallet;
use App\Models\WalletHistory;
use Illuminate\Support\Facades\DB;

class WalletRepository
{
    public function findByUserId(int $userId)
    {
        return Wallet::firstOrCreate(
            ['user_id' => $userId],
            ['total_amount' => 0, 'currency' => 'XAF']
        );
    }

    public function updateBalance(int $userId, float $amount, string $type, string $referenceId = null)
    {
        return DB::transaction(function () use ($userId, $amount, $type, $referenceId) {
            $wallet = $this->findByUserId($userId);

            if ($type === 'credit') {
                $wallet->increment('total_amount', $amount);
            } else {
                $wallet->decrement('total_amount', $amount);
            }

            return WalletHistory::create([
                'user_id' => $userId,
                'type' => $type,
                'amount' => $amount,
                'balance' => $wallet->total_amount,
                'transaction_type' => 'mobile_money',
                'description' => 'Mobile Money ' . ucfirst($type) . ' - ' . $referenceId,
                'datetime' => now(),
            ]);
        });
    }
}
