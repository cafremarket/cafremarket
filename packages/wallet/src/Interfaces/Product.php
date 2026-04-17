<?php

namespace Incevio\Package\Wallet\Interfaces;

interface Product extends Wallet
{
    public function canBuy(Customer $customer, int $quantity = 1, ?bool $force = null): bool;

    /**
     * @return float|int
     */
    public function getAmountProduct(Customer $customer);

    public function getMetaProduct(): ?array;

    public function getUniqueId(): string;
}
