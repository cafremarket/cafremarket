<?php

namespace App\Http\Controllers\Storefront;

use App\Events\Message\MessageReplied;
use App\Events\Message\NewMessage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Validations\ArchiveMessageRequest;
use App\Http\Requests\Validations\ContactSellerRequest;
use App\Http\Requests\Validations\OrderConversationRequest;
use App\Http\Requests\Validations\ReplyMyMessageRequest;
use App\Models\Message;
use App\Models\Order;
use App\Models\Reply;
use App\Models\Shop;
use App\Services\OrderChatSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Incevio\Package\LiveChat\Models\ChatConversation;

class ConversationController extends Controller
{
    /**
     * Contact seller — uses unified LiveChat when available.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function contact(ContactSellerRequest $request, $slug)
    {
        $shop = Shop::select(['id'])->where('slug', $slug)->approved()->firstOrFail();
        $customerId = Auth::guard('customer')->id();

        $text = trim((string) $request->input('message'));
        $subject = trim((string) $request->input('subject'));
        if ($subject !== '') {
            $text = ($text !== '' ? $subject."\n\n".$text : $subject);
        }

        $conversation = ChatConversation::query()
            ->where('shop_id', $shop->id)
            ->where('customer_id', $customerId)
            ->when(
                Schema::hasColumn('chat_conversations', 'order_id'),
                fn ($q) => $q->whereNull('order_id')
            )
            ->first();

        if ($conversation) {
            $conversation->bumpLastMessage($text, true);
            $conversation->replies()->create([
                'customer_id' => $customerId,
                'user_id' => null,
                'reply' => $text,
                'read' => false,
            ]);
        } else {
            $attrs = [
                'shop_id' => $shop->id,
                'customer_id' => $customerId,
                'message' => $text,
                'status' => ChatConversation::STATUS_NEW,
            ];
            if (Schema::hasColumn('chat_conversations', 'order_id')) {
                $attrs['order_id'] = null;
            }
            $conversation = ChatConversation::create($attrs);
            $conversation->replies()->create([
                'customer_id' => $customerId,
                'user_id' => null,
                'reply' => $text,
                'read' => false,
            ]);
        }

        return back()->with('success', trans('theme.notify.message_sent'));
    }

    /**
     * Show a legacy Message thread (when LiveChat inbox is not used).
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, Message $message)
    {
        abort_unless(
            (int) $message->customer_id === (int) Auth::guard('customer')->id(),
            403
        );

        $message->markAsRead();
        $message->load(['replies.attachments', 'attachments', 'shop']);

        return view('theme::contents.message', compact('message'));
    }

    /**
     * Reply on a legacy Message thread.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function reply(ReplyMyMessageRequest $request, Message $message)
    {
        abort_unless(
            (int) $message->customer_id === (int) Auth::guard('customer')->id(),
            403
        );

        $userId = Auth::user()->id;
        $isCustomer = Auth::guard('customer')->check();

        $reply = new Reply;
        $reply->reply = $request->input('reply');

        if ($isCustomer) {
            $reply->customer_id = $userId;
        } else {
            $reply->user_id = $userId;
        }

        $message->replies()->save($reply);
        $message->hasNewReply();

        if ($request->hasFile('photo')) {
            $reply->saveAttachments($request->file('photo'));
        }

        event(new MessageReplied($reply));

        return back()->with('success', trans('theme.notify.message_sent'));
    }

    /**
     * Contact seller about an order — same LiveChat as product chat.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function order_conversation(OrderConversationRequest $request, Order $order)
    {
        $userId = Auth::user()->id;
        $isCustomer = Auth::guard('customer')->check();

        $replyText = trim((string) ($request->input('message') ?? ''));
        $shareOrder = $request->boolean('share_order', true);
        $attachment = $request->hasFile('photo') ? $request->file('photo') : null;

        if ($request->has('goods_received')) {
            $order->mark_as_goods_received();
        }

        OrderChatSyncService::sendToShopChat(
            $order->fresh(['shop', 'customer', 'inventories.image']),
            $replyText,
            $isCustomer ? 'customer' : 'merchant',
            $shareOrder || $replyText !== '',
            $isCustomer ? null : $userId,
            $attachment
        );

        return back()->with('success', trans('theme.notify.message_sent'));
    }

    /**
     * Archive a legacy message conversation.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\Response
     */
    public function archive(ArchiveMessageRequest $request, Message $message)
    {
        $message->archive();

        return back()->with('success', trans('theme.message_archived'));
    }
}
