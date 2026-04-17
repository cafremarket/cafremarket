<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Validations\ConfirmGoodsReceivedRequest;
use App\Http\Requests\Validations\OrderDetailRequest;
use App\Http\Resources\ConversationResource;
use App\Http\Resources\OrderLightResource;
use App\Http\Resources\OrderResource;
use App\Models\Message;
use App\Models\Order;
use App\Models\Reply;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
     * Display order conversation page.
     *
     *
     * @return ConversationResource
     */
    public function conversation(OrderDetailRequest $request, Order $order)
    {
        $order->load(['shop:id,name,slug', 'conversation.replies', 'conversation.replies.attachments']);

        if (! $order->conversation) {
            return response()->json([
                'message' => trans('api.welcome_chat'),
            ]);
        }

        return new ConversationResource($order->conversation);
    }

    /**
     * Start/Replay a order conversation.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return ConversationResource
     */
    public function save_conversation(OrderDetailRequest $request, Order $order)
    {
        $user_id = Auth::user()->id;

        $replyText = trim((string) ($request->input('message') ?? ''));
        if ($replyText === '' && ($request->hasFile('photo') || $request->filled('photo'))) {
            $replyText = ' ';
        }

        if ($order->conversation) {
            $msg = new Reply;
            $msg->reply = $replyText;

            if (Auth::guard('api')->check()) {
                $msg->customer_id = $user_id;
            } else {
                $msg->user_id = $user_id;
            }

            $order->conversation->replies()->save($msg);
        } else {
            $msg = new Message;
            $msg->message = $replyText;
            $msg->shop_id = $order->shop_id;

            if (Auth::guard('api')->check()) {
                $msg->subject = trans('theme.defaults.new_message_from', ['sender' => Auth::user()->getName()]);
                $msg->customer_id = $user_id;
            } else {
                $msg->user_id = $user_id;
            }

            $order->conversation()->save($msg);
        }

        // Update the order if goods_received
        if ($request->has('goods_received')) {
            $order->goods_received();
        }

        try {
            if ($request->hasFile('photo')) {
                $msg->saveAttachments($request->file('photo'));
            } elseif ($request->filled('photo')) {
                $msg->saveAttachments(create_file_from_base64($request->get('photo')));
            }
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => $e->getMessage() ?: 'Could not store attachment.',
            ], 422);
        }

        $order->load(['shop:id,name,slug', 'conversation.replies', 'conversation.replies.attachments']);

        return new ConversationResource($order->conversation);
    }

    /**
     * Buyer confirmed goods received
     *
     *
     * @return OrderResource
     */
    public function goods_received(ConfirmGoodsReceivedRequest $request, Order $order)
    {
        $order->mark_as_goods_received();

        return new OrderResource($order);
    }

    /**
     * Track order shipping.
     *
     *
     * @return \Illuminate\Http\Response
     */
    public function track(Request $request, Order $order)
    {
        $url = $order->getTrackingUrl();

        return response()->json(['tracking_url' => $url], 200);
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
}
