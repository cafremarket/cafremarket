<?php

namespace Incevio\Package\Wallet\Interfaces;

interface Rateable
{
    /**
     * @param  int  $amount
     */
    public function withAmount($amount): self;

    public function withCurrency(Wallet $wallet): self;

    /**
     * @return int|float
     */
    public function convertTo(Wallet $wallet);
}
