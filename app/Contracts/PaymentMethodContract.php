<?php

namespace App\Contracts;

use Illuminate\Http\Request;
use Incevio\Package\Payfast\Services\PayfastPaymentService;

interface PaymentMethodContract
{
    /**
     * This will be return/redirect end-point after payment.
     * Use this end-point as the success return point point
     * when a service needs multiple redirect points
     *
     * @return void
     */
    public function orderReturn(Request $request, PayfastPaymentService $payfast, string $order_ids);

    public function depositReturn(Request $request);
}
