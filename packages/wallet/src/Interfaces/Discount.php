<?php

namespace Incevio\Package\Wallet\Interfaces;

interface Discount extends Product
{
    /**
     * @return int|float
     */
    public function getPersonalDiscount(Customer $customer);
}
