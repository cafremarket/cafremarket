<?php

namespace Incevio\Package\LiveChat\Http\Controllers;;

use App\Models\Shop;
use App\Events\Chat\NewMessageEvent;
use App\Services\ChatSocketPublisher;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Incevio\Package\LiveChat\Http\Requests\ChatConversationRequest;
use Incevio\Package\LiveChat\Http\Requests\SaveChatConversationRequest;
use Incevio\Package\LiveChat\Models\ChatConversation;


// use Illuminate\Broadcasting\InteractsWithSockets;
// use App\Http\Requests\Validations\OrderDetailRequest;

class ChatController extends Controller
{
    /**
     * Show feedback form.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return \Illuminate\Http\Response
     */
    public function conversation(ChatConversationRequest $request, Shop $shop)
    {
        $conversation = ChatConversation::where([
            'customer_id' => Auth::guard('customer')->id(),
            'shop_id' => $shop->id,
        ])->with(['replies.attachments'])->first();

        return response()->json($conversation);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return \Illuminate\Http\Response
     */
    public function save(SaveChatConversationRequest $request)
    {
        $shop = Shop::where('slug', $request->shop_slug)->first();

        if (!$shop) {
            return response(trans('responses.404'), 404);
        }

        $replyText = trim((string) ($request->input('message') ?? ''));
        if ($replyText === '' && ($request->hasFile('photo') || $request->filled('photo'))) {
            $replyText = livechat_message_for_attachment_only();
        }

        if ($replyText === '' && ! $request->hasFile('photo') && ! $request->filled('photo')) {
            return response()->json(['message' => trans('validation.required', ['attribute' => 'message'])], 422);
        }

        $conversation = ChatConversation::where([
            'customer_id' => $request->customer_id,
            'shop_id' => $shop->id
        ])->first();

        if ($conversation) {
            $conversation->markAsUnread();
            $msg_object = $conversation->replies()->create([
                'customer_id' => $request->customer_id,
                'user_id' => $request->user_id,
                'reply' => $replyText,
            ]);
        } elseif ($request->customer_id) {
            $conversation = ChatConversation::create([
                'shop_id' => $shop->id,
                'customer_id' => $request->customer_id,
                'message' => $replyText,
                'status' => ChatConversation::STATUS_NEW,
            ]);

            $msg_object = $conversation->replies()->create([
                'customer_id' => $request->customer_id,
                'user_id' => $request->user_id,
                'reply' => $replyText,
            ]);
        } else {
            return response(trans('responses.unauthorized'), 401);
        }

        if ($request->hasFile('photo')) {
            $msg_object->saveAttachments($request->file('photo'));
        } elseif ($request->filled('photo')) {
            $msg_object->saveAttachments(create_file_from_base64($request->get('photo')));
        }

        $attachmentsPayload = livechat_socket_attachments_payload($msg_object);
        $conversation->refresh();

        try {
            event(new NewMessageEvent($msg_object, $replyText));
        } catch (\Throwable $e) {
            report($e);
        }

        $room = get_chat_room_name($shop->id.$request->customer_id);
        ChatSocketPublisher::publish($room, 'chat.message', [
            'text' => $replyText,
            'sender_type' => 'customer',
            'conversation_id' => $conversation->id,
            'attachments' => $attachmentsPayload,
        ]);

        ChatSocketPublisher::publish(get_vendor_chat_room_id($shop), 'chat.message', [
            'text' => $replyText,
            'sender_type' => 'customer',
            'conversation_id' => $conversation->id,
            'customer_id' => $request->customer_id,
            'time' => $conversation->updated_at->diffForHumans(),
            'attachments' => $attachmentsPayload,
        ]);

        return response()->json([
            'status' => 'ok',
            'conversation_id' => $conversation->id,
        ], 200);
    }
}
