<?php

namespace Incevio\Package\Wallet\Interfaces;

use Incevio\Package\Wallet\Models\Transfer;

interface Exchangeable
{
    /**
     * @param  int  $amount
     */
    public function exchange(Wallet $to, $amount, ?array $meta = null): Transfer;

    /**
     * @param  int  $amount
     */
    public function safeExchange(Wallet $to, $amount, ?array $meta = null): ?Transfer;

    /**
     * @param  int  $amount
     */
    public function forceExchange(Wallet $to, $amount, ?array $meta = null): Transfer;
}
