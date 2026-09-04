<?php

namespace Incevio\Package\LiveChat\Policies;

use App\Helpers\Authorize;
use App\Models\User;
use Incevio\Package\LiveChat\Models\ChatConversation;

class ChatConversationPolicy
{
    /**
     * Merchants with a shop can always manage their store chats.
     */
    protected function merchantCanChat(User $user): bool
    {
        return (bool) $user->merchantId();
    }

    /**
     * Determine whether the user can view chat conversations.
     */
    public function index(User $user)
    {
        if ($this->merchantCanChat($user)) {
            return true;
        }

        return (new Authorize($user, 'view_chat_conversation'))->check();
    }

    /**
     * Determine whether the user can reply to chat conversations.
     */
    public function reply(User $user)
    {
        if ($this->merchantCanChat($user)) {
            return true;
        }

        return (new Authorize($user, 'reply_chat_conversation'))->check();
    }
}
