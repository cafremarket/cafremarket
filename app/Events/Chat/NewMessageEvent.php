<?php

namespace App\Events\Chat;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired after a chat reply is stored.
 * Realtime delivery uses ChatSocketPublisher → chat-ws-node (not Laravel broadcasting).
 * Keep this as a plain event so listeners (email/push) never block the HTTP reply.
 */
class NewMessageEvent
{
    use Dispatchable, SerializesModels;

    public $text;

    public $msg_obj;

    /**
     * @param  mixed  $msg_obj
     * @param  string  $text
     */
    public function __construct($msg_obj, $text)
    {
        $this->msg_obj = $msg_obj;
        $this->text = $text;
    }
}
