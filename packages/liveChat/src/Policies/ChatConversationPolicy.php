<?php

namespace Incevio\Package\LiveChat\Policies;

use App\Helpers\Authorize;
use App\Models\User;
use Incevio\Package\LiveChat\Models\ChatConversation;

class ChatConversationPolicy
{
    /**
     * Determine whether the user can view chat conversations.
     */
    public function index(User $user)
    {
        return (new Authorize($user, 'view_chat_conversation'))->check();
    }

    /**
     * Determine whether the user can reply to chat conversations.
     */
    public function reply(User $user)
    {
        return (new Authorize($user, 'reply_chat_conversation'))->check();
    }
}
