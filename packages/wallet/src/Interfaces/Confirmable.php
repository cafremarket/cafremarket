<?php

namespace Incevio\Package\Wallet\Interfaces;

use Incevio\Package\Wallet\Models\Transaction;

interface Confirmable
{
    public function confirm(Transaction $transaction): bool;

    public function safeConfirm(Transaction $transaction): bool;

    public function forceConfirm(Transaction $transaction): bool;
}
