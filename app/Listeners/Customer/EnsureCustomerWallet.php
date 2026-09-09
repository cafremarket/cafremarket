<?php

namespace App\Listeners\Customer;

use App\Events\Customer\CustomerCreated;
use App\Events\Customer\Registered;

class EnsureCustomerWallet
{
    public function handle($event): void
    {
        $customer = $event->customer ?? null;

        if ($customer && function_exists('ensure_customer_wallet')) {
            ensure_customer_wallet($customer);
        }
    }

    public function subscribe($events): array
    {
        return [
            Registered::class => 'handle',
            CustomerCreated::class => 'handle',
        ];
    }
}
