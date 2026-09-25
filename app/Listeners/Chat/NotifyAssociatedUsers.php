<?php

namespace App\Listeners\Chat;

use App\Events\Chat\NewMessageEvent;
use App\Notifications\Chat\NewMessage;
use App\Services\FCMService;
use Illuminate\Support\Str;

class NotifyAssociatedUsers
{
    /**
     * Handle immediately (not queued) so chat pushes arrive even if queue workers are down.
     *
     * @return void
     */
    public function handle(NewMessageEvent $event)
    {
        $reply = $event->msg_obj;
        $conversation = $reply->repliable ?? null;

        if (! $conversation) {
            return;
        }

        $conversation->loadMissing(['shop.owner', 'customer']);

        $preview = Str::limit(trim(strip_tags((string) $event->text)), 120);
        if ($preview === '') {
            $preview = 'New message';
        }

        $shop = $conversation->shop;
        $customer = $conversation->customer;
        $conversationId = (int) ($conversation->id ?? 0);

        if ($reply->customer_id) {
            // Customer → merchant (vendor app)
            if ($shop) {
                $sender = optional($customer)->getName() ?: 'Customer';
                $receipent = $shop->getName();

                try {
                    safe_notify($shop, new NewMessage($receipent, $sender, $event->text, $conversation), 'chat new message');
                } catch (\Throwable $e) {
                    report($e);
                }

                $this->pushToVendorShop($shop, [
                    'title' => trans('notifications.new_chat_message_title'),
                    'body' => $sender.': '.$preview,
                ], [
                    'type' => 'live_chat',
                    'conversation_id' => $conversationId,
                    'customer_id' => (int) $reply->customer_id,
                ]);
            }
        } else {
            // Merchant → customer (customer app)
            if ($customer) {
                $shopName = optional($shop)->getName() ?: 'Shop';

                $this->pushToToken(optional($customer)->fcm_token, [
                    'title' => $shopName,
                    'body' => $preview,
                ], 'customer', [
                    'type' => 'live_chat',
                    'conversation_id' => $conversationId,
                    'shop_id' => (int) ($conversation->shop_id ?? 0),
                ]);
            }
        }
    }

    protected function pushToVendorShop($shop, array $notification, array $data = []): void
    {
        FCMService::sendToShop($shop, $notification, $data);
    }

    protected function pushToToken($token, array $notification, string $audience, array $data = []): void
    {
        FCMService::send($token, $notification, $audience, $data);
    }
}
