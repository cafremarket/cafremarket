<?php

namespace Incevio\Package\LiveChat\Http\Controllers\Api;

use App\Events\Chat\NewMessageEvent;
use App\Http\Resources\ConversationResource;
use App\Models\Customer;
use App\Models\Shop;
use App\Http\Controllers\Controller;
// use App\Http\Requests\Validations\OrderDetailRequest;
// use App\Http\Requests\Validations\DirectCheckoutRequest;
// use App\Events\Chat\NewMessageEvent;
use App\Models\User;
use App\Services\ChatSocketPublisher;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Incevio\Package\LiveChat\Http\Requests\ChatConversationRequest;
use Incevio\Package\LiveChat\Http\Requests\SaveChatConversationRequest;
use Incevio\Package\LiveChat\Http\Requests\ViewChatConversationRequest;
use Incevio\Package\LiveChat\Models\ChatConversation;

// use Illuminate\Support\Str;
// use Illuminate\Support\Facades\DB;
// use Symfony\Component\HttpFoundation\File\File;
// use Illuminate\Http\UploadedFile;

class ConversationController extends Controller
{
    /**
     * Show all conversations
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return \Illuminate\Http\Response
     */
    public function conversations(Request $request)
    {
        if (is_incevio_package_loaded('livechat')) {
            $conversations = ChatConversation::where('customer_id', Auth::guard('api')->id())->get();

            return ConversationResource::collection($conversations);
        }

        return response()->json([]);
    }

    /**
     * Show single conversation
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return \Illuminate\Http\Response
     */
    public function conversation(ChatConversationRequest $request, Shop $shop)
    {
        if (is_incevio_package_loaded('livechat')) {
            $conversation = ChatConversation::where([
                'customer_id' => Auth::guard('api')->id(),
                'shop_id' => $shop->id,
            ])->with(['replies.attachments'])->first();

            if ($conversation) {
                return new ConversationResource($conversation);
            }

            return response()->json([
                'message' => trans('api.welcome_chat'),
            ]);
        }

        return response()->json([]);
    }

    /**
     * Save message
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return \Illuminate\Http\Response
     */
    public function save_conversation(SaveChatConversationRequest $request, Shop $shop)
    {
        if (is_incevio_package_loaded('livechat')) {
            $msg_object = null;
            $replyText = trim((string) ($request->input('message') ?? ''));
            if ($replyText === '' && ($request->hasFile('photo') || $request->filled('photo'))) {
                $replyText = livechat_message_for_attachment_only();
            }

            if ($replyText === '' && ! $request->hasFile('photo') && ! $request->filled('photo')) {
                return response()->json(['message' => trans('validation.required', ['attribute' => 'message'])], 422);
            }

            $conversation = ChatConversation::where([
                'customer_id' => $request->customer_id,
                'shop_id' => $shop->id,
            ])->first();

            if ($conversation) {
                $conversation->markAsUnread();
                $msg_object = $conversation->replies()->create([
                    'customer_id' => $request->customer_id,
                    'user_id' => $request->user_id,
                    'reply' => $replyText,
                ]);

                try {
                    if ($request->hasFile('photo')) {
                        $msg_object->saveAttachments($request->file('photo'));
                    } elseif ($request->filled('photo')) {
                        $msg_object->saveAttachments(create_file_from_base64($request->get('photo')));
                    }
                } catch (\Throwable $e) {
                    report($e);

                    return response()->json([
                        'message' => $e->getMessage() ?: 'Could not store attachment.',
                    ], 422);
                }
            } elseif ($request->customer_id) {
                $conversation = ChatConversation::create([
                    'shop_id' => $shop->id,
                    'customer_id' => $request->customer_id,
                    'message' => $replyText,
                    'status' => ChatConversation::STATUS_NEW,
                ]);

                // Keep a consistent reply object for realtime updates.
                $msg_object = $conversation->replies()->create([
                    'customer_id' => $request->customer_id,
                    'user_id' => $request->user_id,
                    'reply' => $replyText,
                ]);

                try {
                    if ($request->hasFile('photo')) {
                        $msg_object->saveAttachments($request->file('photo'));
                    } elseif ($request->filled('photo')) {
                        $msg_object->saveAttachments(create_file_from_base64($request->get('photo')));
                    }
                } catch (\Throwable $e) {
                    report($e);

                    return response()->json([
                        'message' => $e->getMessage() ?: 'Could not store attachment.',
                    ], 422);
                }
            } else {
                return response(trans('responses.unauthorized'), 401);
            }

            // Do not fail message sending when realtime provider fails.
            if ($msg_object) {
                $attachmentsPayload = livechat_socket_attachments_payload($msg_object);
                $conversation->refresh();

                try {
                    event(new NewMessageEvent($msg_object, $replyText));
                } catch (\Throwable $e) {
                    report($e);
                }

                ChatSocketPublisher::publish(
                    get_chat_room_name($shop->id.$request->customer_id),
                    'chat.message',
                    [
                        'text' => $replyText,
                        'sender_type' => 'customer',
                        'conversation_id' => $conversation->id,
                        'attachments' => $attachmentsPayload,
                    ]
                );

                // Also notify vendor room so merchant sidebar/conversation updates in realtime.
                ChatSocketPublisher::publish(
                    get_vendor_chat_room_id($shop),
                    'chat.message',
                    [
                        'text' => $replyText,
                        'sender_type' => 'customer',
                        'conversation_id' => $conversation->id,
                        'customer_id' => $request->customer_id,
                        'time' => $conversation->updated_at->diffForHumans(),
                        'attachments' => $attachmentsPayload,
                    ]
                );
            }

            $conversation->load(['replies.attachments']);

            return new ConversationResource($conversation);
        }

        return response()->json([]);
    }

    /**
     * show all conversation
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request)
    {
        $chats = \Incevio\Package\LiveChat\Models\ChatConversation::mine()->get();

        return ConversationResource::collection($chats);
    }

    // /**
    //  * show single conversation
    //  * @param ViewChatConversationRequest $request
    //  * @param $id
    //  * @return ConversationResource
    //  */
    // public function show(ViewChatConversationRequest $request, $id)
    // {
    //     $vendorId = Auth::guard('vendor_api')->id();
    //     $shopId = User::where('id', $vendorId)->pluck('shop_id')->first();

    //     $conversation = ChatConversation::where([
    //         'shop_id' => $shopId,
    //         'customer_id' => $id,
    //     ])->with('replies')->first();

    //     $conversation->markAsRead();

    //     return new ConversationResource($conversation);
    // }

    /**
     * show single conversation
     * @param ViewChatConversationRequest $request
     * @param ChatConversation $chat
     * @return ConversationResource
     */
    public function show(ViewChatConversationRequest $request, ChatConversation $chat)
    {
        $chat->markAsRead();

        $chat->load('replies');

        return new ConversationResource($chat);
    }

    /**
     * save chat
     * @param SaveChatConversationRequest $request
     * @param ChatConversation $chat
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function reply(SaveChatConversationRequest $request, ChatConversation $chat)
    {
        $replyText = trim((string) $request->input('message', ''));
        if ($replyText === '' && ($request->hasFile('photo') || $request->filled('photo'))) {
            $replyText = livechat_message_for_attachment_only();
        }

        if ($replyText === '' && ! $request->hasFile('photo') && ! $request->filled('photo')) {
            return response()->json(['message' => trans('validation.required', ['attribute' => 'message'])], 422);
        }

        $reply = $chat->replies()->create([
            'customer_id' => $chat->customer_id,
            'user_id' => Auth::guard('vendor_api')->id(),
            'reply' => $replyText,
        ]);

        if ($request->hasFile('photo')) {
            $reply->saveAttachments($request->file('photo'));
        } elseif ($request->filled('photo')) {
            $reply->saveAttachments(create_file_from_base64($request->get('photo')));
        }

        $attachmentsPayload = livechat_socket_attachments_payload($reply);

        try {
            event(new NewMessageEvent($reply, $replyText));
        } catch (\Throwable $e) {
            report($e);
        }

        ChatSocketPublisher::publish(
            get_chat_room_name($chat->shop_id.$chat->customer_id),
            'chat.message',
            [
                'text' => $replyText,
                'sender_type' => 'merchant',
                'conversation_id' => $chat->id,
                'reply_id' => $reply->id,
                'attachments' => $attachmentsPayload,
            ]
        );

        return response()->json(['message' => 'Replied successfully'], 200);
    }

    /**
     * Create File from base64 strings:
     */
    /* public function createFileFromBase64($base64File)
    {
        $fileData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64File));
        // save it to temporary dir first.
        $tmpFilePath = sys_get_temp_dir() . '/' . Str::uuid()->toString().".png";
        file_put_contents($tmpFilePath, $fileData);
        // this just to help us get file info.
        $tmpFile = new File($tmpFilePath);

        $file = new UploadedFile(
            $tmpFile->getPathname(),
            $tmpFile->getFilename(),
            $tmpFile->getMimeType(),
            0,
            true // Mark it as test, since the file isn't from real HTTP POST.
        );

        return $file;
    }*/
}
