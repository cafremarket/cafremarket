<?php

namespace Incevio\Package\LiveChat\Database\Seeds;

use App\Helpers\PackageSeeder;

class ChatSeeder extends PackageSeeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Seed Permissions (also covered by migration seed_chat_conversation_permissions)
        $actions = 'view,reply';
        $this->seedPermissions('Chat Conversation', 'Merchant', $actions);
    }
}
