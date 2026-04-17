<?php

namespace Incevio\Package\MPesa\Http\Controllers;

use App\Models\Order;
use App\Events\Order\OrderCreated;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Incevio\Package\MPesa\Services\MPesaPaymentService;

class PaymentController extends Controller
{
    public function showConfirmationForm(Request $request, Order $order)
    {
        if ($order->isPaid()) {
            return redirect()->to(url('mpesa/' . $order->id . '/complete'))
                ->with('success', trans('theme.notify.order_placed'));
        }

        return view('mpesa::payment_confirmation_form', compact('order'));
    }

    /**
     * JSON endpoint for polling. If order not yet paid, query M-Pesa API so we detect
     * payment even when callback does not fire (e.g. sandbox / localhost).
     */
    public function paymentStatus(Request $request, Order $order)
    {
        $order->refresh();

        if ($order->isPaid()) {
            return response()->json(['paid' => true]);
        }

        if ($order->payment_ref_id) {
            $cacheKey = 'mpesa_status_check_' . $order->id;
            if (! Cache::has($cacheKey)) {
                if (config('mpesa.query_enabled', true)) {
                    try {
                        $mpesa = new MPesaPaymentService($request);
                        $response = $mpesa->verifyPayment($order->payment_ref_id);
                        if ($response !== null) {
                            $json = json_decode($response);
                            if ($json) {
                                $success = isset($json->output_ResponseCode)
                                    ? (($json->output_ResponseCode === 'INS-0') || ($json->output_ResponseCode === '0'))
                                    : ((int) ($json->ResultCode ?? 1) === 0);
                                if ($success) {
                                    $order->markAsPaid();
                                    event(new OrderCreated($order));

                                    return response()->json(['paid' => true]);
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        Log::debug('M-Pesa status check: ' . $e->getMessage());
                    }
                }
                Cache::put($cacheKey, 1, now()->addSeconds(10));
            }
        }

        // Callback or another request may have marked the order paid (e.g. first poll 200, second 403)
        $order->refresh();
        if ($order->isPaid()) {
            return response()->json(['paid' => true]);
        }

        return response()->json(['paid' => false]);
    }

    /**
     * Order complete page – show straight after initiating M-Pesa; if not paid yet, show same page with polling.
     */
    public function showComplete(Request $request, Order $order)
    {
        if ($order->isPaid()) {
            return view('theme::order_complete', compact('order'))
                ->with('success', trans('theme.notify.order_placed'));
        }

        return view('mpesa::order_complete', compact('order'));
    }

    /**
     * Confirm order after customer has paid via M-Pesa (Mozambique: query status).
     */
    public function confirm(Request $request, Order $order)
    {
        $order->refresh();
        if ($order->isPaid()) {
            return view('theme::order_complete', compact('order'))
                ->with('success', trans('theme.notify.order_placed'));
        }

        if (! config('mpesa.query_enabled', true)) {
            return redirect()->to(url('mpesa/' . $order->id . '/complete'))
                ->with('info', trans('mpesa::lang.confirm_via_callback'));
        }

        $mpesa = new MPesaPaymentService($request);
        $response = $mpesa->verifyPayment($order->payment_ref_id);
        $json = $response ? json_decode($response) : null;

        if (! $json) {
            return redirect()->route('payment.failed', $order)
                ->withErrors(['payment_error' => trans('mpesa::lang.error_response')]);
        }

        // Mozambique: output_ResponseCode INS-0 or 0 = success
        $success = isset($json->output_ResponseCode)
            ? (($json->output_ResponseCode === 'INS-0') || ($json->output_ResponseCode === '0'))
            : ((int) ($json->ResultCode ?? 1) === 0);

        if ($success) {
            $order->markAsPaid();
            event(new OrderCreated($order));

            return view('theme::order_complete', compact('order'))
                ->with('success', trans('theme.notify.order_placed'));
        }

        Log::info('M-Pesa verify response: ' . $response);

        $message = $json->output_ResponseDesc ?? $json->ResultDesc ?? trans('mpesa::lang.error_response');

        return redirect()->route('payment.failed', $order)
            ->withErrors(['payment_error' => $message]);
    }
}
