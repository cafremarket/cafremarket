<?php

namespace Incevio\Package\Wallet\Interfaces;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Incevio\Package\Wallet\Models\Transaction;
use Incevio\Package\Wallet\Models\Transfer;

interface Wallet
{
    /**
     * @param  int  $amount
     */
    public function deposit($amount, ?array $meta = null, bool $confirmed = true): Transaction;

    /**
     * @param  int  $amount
     */
    public function withdraw($amount, ?array $meta = null, bool $confirmed = true, bool $approved = true): Transaction;

    /**
     * @param  int  $amount
     */
    public function forceWithdraw($amount, ?array $meta = null, bool $confirmed = true): Transaction;

    /**
     * @param  int  $amount
     */
    public function transfer(self $wallet, $amount, ?array $meta = null): Transfer;

    /**
     * @param  int  $amount
     */
    public function safeTransfer(self $wallet, $amount, ?array $meta = null): ?Transfer;

    /**
     * @param  int  $amount
     */
    public function forceTransfer(Wallet $wallet, $amount, ?array $meta = null): Transfer;

    /**
     * @param  int  $amount
     */
    public function canWithdraw($amount, ?bool $allowZero = null): bool;

    /**
     * @return int|float
     */
    public function getBalanceAttribute();

    public function transactions(): MorphMany;
}
