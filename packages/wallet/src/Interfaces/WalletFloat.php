<?php

namespace Incevio\Package\Wallet\Interfaces;

use Incevio\Package\Wallet\Models\Transaction;
use Incevio\Package\Wallet\Models\Transfer;

interface WalletFloat
{
    /**
     * @param  float  $amount
     */
    public function depositFloat($amount, ?array $meta = null, bool $confirmed = true): Transaction;

    /**
     * @param  float  $amount
     */
    public function withdrawFloat($amount, ?array $meta = null, bool $confirmed = true): Transaction;

    /**
     * @param  float  $amount
     */
    public function forceWithdrawFloat($amount, ?array $meta = null, bool $confirmed = true): Transaction;

    /**
     * @param  float  $amount
     */
    public function transferFloat(Wallet $wallet, $amount, ?array $meta = null): Transfer;

    /**
     * @param  float  $amount
     */
    public function safeTransferFloat(Wallet $wallet, $amount, ?array $meta = null): ?Transfer;

    /**
     * @param  float  $amount
     */
    public function forceTransferFloat(Wallet $wallet, $amount, ?array $meta = null): Transfer;

    /**
     * @param  float  $amount
     */
    public function canWithdrawFloat($amount): bool;

    /**
     * @return int|float
     */
    public function getBalanceFloatAttribute();
}
