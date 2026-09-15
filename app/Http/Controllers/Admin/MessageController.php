<?php

namespace App\Http\Controllers\Admin;

use App\Common\Authorizable;
use App\Events\Message\MessageReplied;
use App\Events\Message\NewMessage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Validations\CreateMessageRequest;
use App\Http\Requests\Validations\DraftSendRequest;
use App\Http\Requests\Validations\ReplyMessageRequest;
use App\Models\Message;
use App\Models\Order;
use App\Repositories\Message\MessageRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    use Authorizable;

    private $model;

    private $message;

    /**
     * construct
     */
    public function __construct(MessageRepository $message)
    {
        parent::__construct();

        $this->model = trans('app.model.message');

        $this->message = $message;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function labelOf($label = 1)
    {
        $messages = Message::with('lastReply', 'customer', 'order', 'item')
            ->labelOf($label)->mine()->withCount('replies', 'attachments')
            ->orderBy('updated_at', 'desc')
            ->paginate(getPaginationValue());

        return view('admin.message.index', compact('messages'));
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function statusOf($status = 1)
    {
        $messages = $this->message->statusOf($label);

        return view('admin.message.index', compact('messages'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($type = null)
    {
        return view('admin.message._create', compact('type'));
    }

    public function orderConversation(Request $request, Order $order)
    {
        if (Auth::user()->isFromMerchant() && $order->shop_id != Auth::user()->merchantId()) {
            abort(404);
        }

        // Merchant order page: open existing LiveChat (shop↔customer), not the old Message compose form.
        if (
            Auth::user()->isFromMerchant()
            && class_exists(\Incevio\Package\LiveChat\Models\ChatConversation::class)
            && \Illuminate\Support\Facades\Schema::hasTable('chat_conversations')
            && $order->customer_id
        ) {
            $chat = \App\Services\OrderChatSyncService::findOrCreateShopChat($order);

            if (! $chat) {
                abort(404);
            }

            $chat->markAsRead();
            $chat->markPeerRepliesAsRead('merchant');
            $chat->loadMissing(['replies.attachments', 'customer', 'shop', 'order']);

            return view('liveChat::merchant._order_chat_modal', compact('order', 'chat'));
        }

        $view = Auth::user()->isFromMerchant() ? 'merchant.message._create' : 'admin.message._create';

        return view($view, compact('order'));
    }

    /**
     * A merchant may only ever open/reply to a conversation on their own
     * shop's order — platform staff can open any of them. Mirrors the same
     * check CreateMessageRequest already applies when starting a new one.
     */
    private function abortUnlessMine(Message $message): void
    {
        if (Auth::user()->isFromMerchant() && $message->shop_id != Auth::user()->merchantId()) {
            abort(404);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CreateMessageRequest $request)
    {
        $message = $this->message->store($request);

        event(new NewMessage($message));

        return back()->with('success', trans('messages.created', ['model' => $this->model]));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param int id
     * @return \Illuminate\Http\Response
     */
    public function draftSend(DraftSendRequest $request, $id)
    {
        $this->message->update($request, $id);

        if ($request->has('draft')) {
            return back()->with('success', trans('messages.updated', ['model' => $this->model]));
        }

        return back()->with('success', trans('messages.sent', ['model' => $this->model]));
    }

    /**
     * Display the specified resource.
     *
     * @param int id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        $message = Message::with('replies.attachments', 'replies.customer', 'replies.user')->findOrFail($id);

        $this->abortUnlessMine($message);

        $message->markAsRead();

        $view = Auth::user()->isFromMerchant() ? 'merchant.message.show' : 'admin.message.show';

        return view($view, compact('message'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $message = $this->message->find($id);

        return view('admin.message._edit', compact('message'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param int id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id, $statusOrLabel, $type = 'label')
    {
        $message = $this->message->find($id);

        $backLabel = $message->label;

        $this->message->updateStatusOrLabel($request, $message, $statusOrLabel, $type);

        return redirect()->route('admin.support.message.labelOf', $backLabel)
            ->with('success', trans('messages.updated', ['model' => $this->model]));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param int id
     * @return \Illuminate\Http\Response
     */
    public function massUpdate(Request $request, $statusOrLabel, $type = 'label')
    {
        $this->message->massUpdate($request->ids, $statusOrLabel, $type);

        return response()->json(['success' => trans('messages.updated', ['model' => $this->model])]);
    }

    /**
     * Display the reply form.
     *
     * @param int id
     * @return \Illuminate\Http\Response
     */
    public function reply($id, $template = null)
    {
        $message = Message::findOrFail($id);

        $this->abortUnlessMine($message);

        $view = Auth::user()->isFromMerchant() ? 'merchant.message._reply' : 'admin.message._reply';

        return view($view, compact('message', 'template'));
    }

    /**
     * Display the specified resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param int id
     * @return \Illuminate\Http\Response
     */
    public function storeReply(ReplyMessageRequest $request, $id)
    {
        $this->abortUnlessMine(Message::findOrFail($id));

        $reply = $this->message->storeReply($request, $id);

        event(new MessageReplied($reply));

        return back()->with('success', trans('messages.updated', ['model' => $this->model]));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        $message = $this->message->find($id);

        $backLabel = $message->label;

        $this->message->destroy($message);

        return redirect()->route('admin.support.message.labelOf', $backLabel)
            ->with('success', trans('messages.deleted', ['model' => $this->model]));
    }

    public function massDestroy(Request $request)
    {
        $this->message->massDestroy($request->ids);

        return response()->json(['success' => trans('messages.deleted', ['model' => $this->model])]);
    }
}
