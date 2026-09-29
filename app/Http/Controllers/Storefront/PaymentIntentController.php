<?php

namespace App\Http\Controllers\Storefront;

use App\Exceptions\PaymentFailedException;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PaymentIntent;
use App\Services\Payments\CheckoutPaymentIntentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Waiting screen for mobile-money checkout (no order exists until paid).
 */
class PaymentIntentController extends Controller
{
    public function __construct(private readonly CheckoutPaymentIntentService $intents)
    {
    }

    public function show(PaymentIntent $intent)
    {
        $this->authorizeOwner($intent);

        if ($intent->isCompleted()) {
            return redirect()->to($this->confirmationUrl($intent));
        }

        $state = $this->intents->present($intent);

        return view('theme::payment_waiting', compact('intent', 'state'));
    }

    /**
     * Push the request to the phone. Called once by the waiting page.
     */
    public function initiate(PaymentIntent $intent): JsonResponse
    {
        $this->authorizeOwner($intent);

        return $this->respond($this->intents->initiate($intent));
    }

    public function status(PaymentIntent $intent): JsonResponse
    {
        $this->authorizeOwner($intent);

        return $this->respond($this->intents->refresh($intent, request()->boolean('force')));
    }

    public function cancel(PaymentIntent $intent): JsonResponse
    {
        $this->authorizeOwner($intent);

        return $this->respond($this->intents->cancel($intent));
    }

    public function retry(Request $request, PaymentIntent $intent): JsonResponse
    {
        $this->authorizeOwner($intent);

        try {
            $retry = $this->intents->retry($intent, $request->input('msisdn'));
        } catch (PaymentFailedException $e) {
            return response()->json(['message' => $e->getMessage(), 'redirect_url' => route('cart.index')], 422);
        }

        return response()->json([
            'id' => $retry->uuid,
            'redirect_url' => route('checkout.payment.wait', $retry),
        ]);
    }

    private function respond(PaymentIntent $intent): JsonResponse
    {
        $data = $this->intents->present($intent);

        if ($intent->isCompleted()) {
            $data['redirect_url'] = $this->confirmationUrl($intent);
        }

        return response()->json($data);
    }

    private function confirmationUrl(PaymentIntent $intent): string
    {
        $orders = $intent->orders()->get(['id', 'order_number']);

        session(['confirmed_order_ids' => $orders->pluck('id')->all()]);
        session()->flash('success', trans('theme.notify.order_placed'));

        /** @var Order|null $primary */
        $primary = $orders->first();

        return $primary
            ? route('order.confirmation', ['order_number' => rawurlencode((string) $primary->order_number)])
            : route('account', 'orders');
    }

    private function authorizeOwner(PaymentIntent $intent): void
    {
        abort_unless((int) $intent->customer_id === (int) auth('customer')->id(), 404);
    }
}
