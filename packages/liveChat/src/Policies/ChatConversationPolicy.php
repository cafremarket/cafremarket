<?php

namespace Incevio\Package\LiveChat\Policies;

use App\Helpers\Authorize;
use App\Models\User;
use Incevio\Package\LiveChat\Models\ChatConversation;

class ChatConversationPolicy
{
    /**
     * Determine whether the user can view chat conversations.
     * Shop owners always can (same pattern as Orders); staff need the slug.
     */
    public function index(User $user)
    {
        if ($user->isMerchant() && $user->merchantId()) {
            return true;
        }

        return (new Authorize($user, 'view_chat_conversation'))->check();
    }

    /**
     * Determine whether the user can reply to chat conversations.
     */
    public function reply(User $user)
    {
        if ($user->isMerchant() && $user->merchantId()) {
            return true;
        }

        return (new Authorize($user, 'reply_chat_conversation'))->check();
    }
}
