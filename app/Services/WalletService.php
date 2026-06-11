<?php

namespace App\Services;

use App\Repositories\WalletRepository;

class WalletService
{
    protected $walletRepository;

    public function __construct(WalletRepository $walletRepository)
    {
        $this->walletRepository = $walletRepository;
    }

    public function creditWallet(int $userId, float $amount, string $referenceId = null)
    {
        return $this->walletRepository->updateBalance($userId, $amount, 'credit', $referenceId);
    }

    public function debitWallet(int $userId, float $amount, string $referenceId = null)
    {
        return $this->walletRepository->updateBalance($userId, $amount, 'debit', $referenceId);
    }

    public function getBalance(int $userId)
    {
        $wallet = $this->walletRepository->findByUserId($userId);
        return $wallet->total_amount;
    }
}
