<?php

namespace Incevio\Package\Wallet\Test\Models;

use Incevio\Package\Wallet\Interfaces\Customer;
use Incevio\Package\Wallet\Interfaces\Discount;
use Incevio\Package\Wallet\Services\WalletService;

class ItemDiscount extends Item implements Discount
{
    public function getTable(): string
    {
        return 'items';
    }

    public function getPersonalDiscount(Customer $customer): int
    {
        return app(WalletService::class)
            ->getWallet($customer)
            ->holder_id;
    }
}
