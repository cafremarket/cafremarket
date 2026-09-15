<?php

namespace App\Http\Controllers\Api;

use App\Contracts\PaymentServiceContract as PaymentGateway;
use App\Http\Controllers\Controller;
use App\Http\Requests\Validations\ChangeOrderPaymentMethodRequest;
use App\Http\Requests\Validations\ConfirmGoodsReceivedRequest;
use App\Http\Requests\Validations\OrderDetailRequest;
use App\Http\Resources\ConversationResource;
use App\Http\Resources\OrderLightResource;
use App\Http\Resources\OrderResource;
use App\Exceptions\PaymentFailedException;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Services\Emola\EmolaOrderPaymentService;
use App\Services\Geo\DistanceService;
use App\Services\OrderChatSyncService;
use App\Services\Payments\PaymentService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection a collection of OrderLightResource
     */
    public function index(Request $request)
    {
        $orders = Auth::guard('api')->user()->orders()
            ->with([
                'shop:id,name,slug',
                'inventories:id,title,slug,product_id,download_limit',
                'inventories.image:path,imageable_id,imageable_type',
                'dispute:id,order_id',
                'paymentMethod:id,code',
            ])
            ->paginate(config('mobile_app.view_listing_per_page', 8));

        return OrderLightResource::collection($orders);
    }

    /**
     * Display order detail page.
     *
     *
     * @return OrderResource
     */
    public function show(OrderDetailRequest $request, Order $order)
    {
        $order->load([
            'inventories.attachments',
            'conversation:id,order_id,user_id,customer_id,subject,message,product_id,status,updated_at',
            'conversation.attachments',
            'feedback',
        ]);

        return new OrderResource($order);
    }

    /**
     * Load the unified LiveChat for this order's shop + customer.
     *
     * @return ConversationResource|\Illuminate\Http\JsonResponse
     */
    public function conversation(OrderDetailRequest $request, Order $order)
    {
        $chat = OrderChatSyncService::findShopChat($order);

        if (! $chat) {
            return response()->json([
                'message' => trans('api.welcome_chat'),
            ]);
        }

        $chat->markPeerRepliesAsRead('customer');

        return new ConversationResource($chat->fresh(['replies.attachments', 'shop', 'customer']));
    }

    /**
     * Send into the same LiveChat used for product/seller chat (not Message).
     *
     * @return ConversationResource|\Illuminate\Http\JsonResponse
     */
    public function save_conversation(OrderDetailRequest $request, Order $order)
    {
        $userId = Auth::user()->id;
        $isCustomer = Auth::guard('api')->check();

        $replyText = trim((string) ($request->input('message') ?? ''));
        $shareOrder = $request->boolean('share_order', true);

        $attachment = null;
        if ($request->hasFile('photo')) {
            $attachment = $request->file('photo');
        } elseif ($request->filled('photo')) {
            try {
                $attachment = create_file_from_base64($request->get('photo'));
            } catch (\Throwable $e) {
                report($e);

                return response()->json([
                    'message' => $e->getMessage() ?: 'Could not store attachment.',
                ], 422);
            }
        }

        if ($replyText === '' && ! $attachment && ! $shareOrder) {
            return response()->json([
                'message' => trans('validation.required', ['attribute' => 'message']),
            ], 422);
        }

        if ($request->has('goods_received')) {
            $order->goods_received();
        }

        try {
            $chat = OrderChatSyncService::sendToShopChat(
                $order->fresh(['shop', 'customer', 'inventories.image']),
                $replyText,
                $isCustomer ? 'customer' : 'merchant',
                $shareOrder,
                $isCustomer ? null : $userId,
                $attachment
            );
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => $e->getMessage() ?: 'Could not send message.',
            ], 422);
        }

        if (! $chat) {
            return response()->json(['message' => trans('api.something_went_wrong')], 500);
        }

        return new ConversationResource($chat);
    }

    /**
     * Buyer confirmed goods received
     *
     *
     * @return OrderResource
     */
    public function goods_received(ConfirmGoodsReceivedRequest $request, Order $order)
    {
        if ($order->isDelivered()) {
            return response()->json(['message' => trans('app.order_already_delivered')], 422);
        }

        $order->mark_as_goods_received();

        return new OrderResource($order);
    }

    /**
     * Track order shipping.
     *
     *
     * @return \Illuminate\Http\Response
     */
    public function track(Request $request, Order $order, DistanceService $distanceService)
    {
        $payload = ['tracking_url' => $order->getTrackingUrl()];

        if ($order->canTrack()) {
            $rider = $order->deliveryBoy;
            $shopAddress = $order->shop?->storeAddress();

            $payload['rider'] = [
                'latitude' => (float) $rider->current_latitude,
                'longitude' => (float) $rider->current_longitude,
                'last_location_at' => optional($rider->last_location_at)->toIso8601String(),
                'name' => $rider->getName(),
            ];

            if ($order->customer_latitude && $order->customer_longitude) {
                $payload['customer'] = [
                    'latitude' => (float) $order->customer_latitude,
                    'longitude' => (float) $order->customer_longitude,
                ];

                $payload['eta_km'] = round($distanceService->distanceKm(
                    (float) $rider->current_latitude,
                    (float) $rider->current_longitude,
                    (float) $order->customer_latitude,
                    (float) $order->customer_longitude
                ), 2);
            }

            if ($shopAddress?->latitude && $shopAddress?->longitude) {
                $payload['shop'] = [
                    'latitude' => (float) $shopAddress->latitude,
                    'longitude' => (float) $shopAddress->longitude,
                ];
            }

            $payload['delivery_status_label'] = $order->deliveryStatusLabel();
        }

        return response()->json($payload, 200);
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function invoice(Order $order)
    {
        return $order->invoice('download');
    }

    /**
     * Poll eMola payment status for an order (same idea as M-Pesa order status).
     */
    public function emolaPaymentStatus(Request $request, Order $order, EmolaOrderPaymentService $emolaOrders)
    {
        $customer = Auth::guard('api')->user();
        if (! $customer || (int) $order->customer_id !== (int) $customer->id) {
            return response()->json(['message' => trans('responses.unauthorized')], 403);
        }

        $order->refresh();

        if ($order->isPaid()) {
            return response()->json(['paid' => true]);
        }

        if (optional($order->paymentMethod)->code !== 'emola') {
            return response()->json([
                'paid' => false,
                'message' => trans('theme.emola_resend_not_allowed'),
            ], 400);
        }

        $result = $emolaOrders->syncPaymentStatusFromGateway($order);

        return response()->json([
            'paid' => $result['paid'],
            'message' => $result['message'],
        ]);
    }

    /**
     * Resend eMola USSD payment request for a pending order.
     */
    public function resendEmolaPayment(Request $request, Order $order, EmolaOrderPaymentService $emolaOrders)
    {
        $customer = Auth::guard('api')->user();
        if (! $customer || (int) $order->customer_id !== (int) $customer->id) {
            return response()->json(['message' => trans('responses.unauthorized')], 403);
        }

        if (! $order->canResendEmolaPayment()) {
            return response()->json([
                'message' => trans('theme.emola_resend_not_allowed'),
            ], 403);
        }

        $request->validate([
            'emola_number' => ['required', 'string', 'regex:/^(86|87)\d{7}$/'],
        ], [
            'emola_number.required' => trans('theme.emola_number_required'),
            'emola_number.regex' => trans('theme.emola_number_invalid'),
        ]);

        try {
            $emolaOrders->resendPaymentRequest($order, $request->input('emola_number'));
        } catch (PaymentFailedException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => trans('theme.emola_resend_success'),
        ]);
    }

    /**
     * Only reachable while the order's bank transfer proof sits rejected.
     * Lets the customer either re-upload a proof (payment_method stays
     * 'wire') or switch to a different payment method entirely. Mirrors
     * Storefront\OrderController::changePaymentMethod() — same payment
     * dispatch as checkout, run against an existing order.
     */
    public function changePaymentMethod(ChangeOrderPaymentMethodRequest $request, Order $order, PaymentGateway $payment)
    {
        if (! shop_can_accept_sales($order->shop)) {
            return response()->json([
                'message' => trans('packages.wallet.vendor_sales_require_subscription'),
            ], 422);
        }

        DB::beginTransaction();

        try {
            $paymentMethod = (string) $request->input('payment_method', '');
            $order->payment_method_id = get_id_of_model('payment_methods', 'code', $paymentMethod);
            $order->clearWireTransferRejection();

            if ($paymentMethod === 'wire' && $request->hasFile('wire_transfer_proof')) {
                $uploaded = $order->saveAttachments($request->file('wire_transfer_proof'));
                $proof = $uploaded->first();

                if ($proof) {
                    $order->forceFill([
                        'wire_transfer_proof_path' => $proof->path,
                        'wire_transfer_proof_name' => $proof->name,
                    ]);
                }
            }

            $order->save();
            $order->load('paymentMethod'); // payment_method_id just changed — reload the relation

            $receiver = vendor_get_paid_directly() ? PaymentService::RECEIVER_MERCHANT : PaymentService::RECEIVER_PLATFORM;

            if (in_array($paymentMethod, ['mpesa', 'emola'], true)) {
                persist_order_checkout_fees($order, $paymentMethod);
            }

            $paymentBuilder = $payment->setReceiver($receiver)->setOrderInfo($order);
            if (in_array($paymentMethod, ['mpesa', 'emola'], true)) {
                $paymentBuilder->setAmountWithPlatformFee($order->grand_total, $paymentMethod);
            } else {
                $paymentBuilder->setAmount($order->grand_total);
            }

            $response = $paymentBuilder
                ->setDescription(trans('app.purchase_from', [
                    'marketplace' => get_platform_title(),
                ]))
                ->setConfig()
                ->charge();

            // A web-only gateway redirect (e.g. PayPal) makes no sense for the app.
            if ($response instanceof RedirectResponse) {
                DB::rollback();

                return response()->json([
                    'message' => trans('theme.notify.payment_failed'),
                ], 422);
            }

            switch ($response->status) {
                case PaymentService::STATUS_PAID:
                    if (optional($order->paymentMethod)->code !== 'emola') {
                        $order->markAsPaid();
                    }
                    break;

                case PaymentService::STATUS_PENDING:
                    if ($order->paymentMethod->code == 'cod') {
                        $order->order_status_id = Order::STATUS_CONFIRMED;
                        $order->payment_status = Order::PAYMENT_STATUS_UNPAID;
                    } else {
                        $order->order_status_id = Order::STATUS_WAITING_FOR_PAYMENT;
                        $order->payment_status = Order::PAYMENT_STATUS_PENDING;
                    }
                    break;

                case PaymentService::STATUS_ERROR:
                    $order->payment_status = Order::PAYMENT_STATUS_PENDING;
                    $order->order_status_id = Order::STATUS_PAYMENT_ERROR;
                    break;

                default:
                    throw new PaymentFailedException(trans('theme.notify.payment_failed'));
            }

            $order->save();
        } catch (PaymentFailedException $e) {
            DB::rollback();

            Log::warning('Order #'.$order->id.' change payment method failed: '.$e->getMessage());

            return response()->json(['message' => $e->getMessage()], 422);
        } catch (Exception $e) {
            DB::rollback();

            Log::error('Order #'.$order->id.' change payment method failed: '.$e->getMessage(), [
                'exception' => $e,
            ]);

            return response()->json(['message' => $e->getMessage()], 422);
        }

        DB::commit();

        $order->load(['inventories.attachments', 'paymentMethod', 'shop']);

        $message = trans('theme.notify.order_placed');
        if (! empty($response->paymentNotice)) {
            $message = $response->paymentNotice;
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => new OrderResource($order),
        ]);
    }
}
