<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\PaymentFailedException;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderLightResource;
use App\Http\Resources\OrderResource;
use App\Models\PaymentIntent;
use App\Services\Payments\CheckoutPaymentIntentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Mobile app waiting screen for mobile-money checkout (no order until paid).
 */
class PaymentIntentController extends Controller
{
    public function __construct(private readonly CheckoutPaymentIntentService $intents)
    {
    }

    public function show(Request $request, PaymentIntent $intent): JsonResponse
    {
        $this->authorizeOwner($intent);

        return self::intentResponse($this->intents->refresh($intent, $request->boolean('force')));
    }

    public function cancel(PaymentIntent $intent): JsonResponse
    {
        $this->authorizeOwner($intent);

        return self::intentResponse($this->intents->cancel($intent));
    }

    /**
     * New payment request for the same carts (optionally to another number).
     */
    public function retry(Request $request, PaymentIntent $intent): JsonResponse
    {
        $this->authorizeOwner($intent);

        try {
            $retry = $this->intents->initiate($this->intents->retry($intent, $request->input('msisdn')));
        } catch (PaymentFailedException $e) {
            return response()->json(['message' => $e->getMessage(), 'error' => $e->getMessage()], 422);
        }

        return self::intentResponse($retry);
    }

    /**
     * 200 with the created orders once paid (same keys as a normal checkout),
     * 202 while the customer still has to approve on the phone.
     */
    public static function intentResponse(PaymentIntent $intent): JsonResponse
    {
        $data = app(CheckoutPaymentIntentService::class)->present($intent);

        if (! $intent->isCompleted()) {
            return response()->json([
                'message' => $data['message'],
                'payment_intent' => $data,
            ], $intent->isFinal() ? 200 : 202);
        }

        $orders = $intent->orders()->with(['shop', 'paymentMethod', 'inventories.image', 'customer'])->get();
        $primary = $orders->first();

        return response()->json([
            'message' => trans('theme.notify.order_placed'),
            'payment_intent' => $data,
            'order' => $primary ? new OrderResource($primary) : null,
            'orders' => $orders->map(fn ($order) => $order->is($primary)
                ? (new OrderResource($order))->resolve()
                : (new OrderLightResource($order))->resolve())->values(),
        ], 200);
    }

    private function authorizeOwner(PaymentIntent $intent): void
    {
        abort_unless((int) $intent->customer_id === (int) auth('api')->id(), 404);
    }
}
