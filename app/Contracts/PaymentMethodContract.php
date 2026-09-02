<?php

namespace App\Contracts;

use Illuminate\Http\Request;

interface PaymentMethodContract
{
    /**
     * This will be return/redirect end-point after payment.
     * Use this end-point as the success return point point
     * when a service needs multiple redirect points
     *
     * @return void
     */
    public function orderReturn(Request $request, string $order_ids);

    public function depositReturn(Request $request);
}
