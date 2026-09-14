<?php

namespace App\Listeners\Message;

use App\Events\Message\MessageReplied;
use App\Notifications\Message\Replied as MessageRepliedNotification;
use App\Services\FCMService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Str;

class NotifyAssociatedUsersMessagetReplied implements ShouldQueue
{
    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 5;

    /**
     * Handle the event.
     *
     * @return void
     */
    public function handle(MessageReplied $event)
    {
        if (! config('system_settings')) {
            setSystemConfig();
        }

        $repliable = $event->reply->repliable;

        // Set shop configuration
        if ($repliable && $repliable->shop_id && ! config('shop_settings')) {
            setSystemConfig($repliable->shop_id);
        }

        $preview = Str::limit(trim(strip_tags((string) $event->reply->reply)), 120);
        if ($preview === '') {
            $preview = 'New reply';
        }

        $subject = optional($repliable)->subject;
        $data = [
            'type' => 'message',
            'message_id' => (int) optional($repliable)->id,
            'order_id' => (int) (optional($repliable)->order_id ?? 0),
        ];

        if ($event->reply->user_id) {
            // Staff/merchant replied → notify customer
            $senderName = optional($event->reply->user)->getName() ?: 'Shop';
            $customerToken = optional(optional($repliable)->customer)->fcm_token;

            if ($customerToken) {
                FCMService::send($customerToken, [
                    'title' => trans('notifications.message_replied.subject', [
                        'user' => $senderName,
                        'subject' => $subject,
                    ]),
                    'body' => $preview,
                ], 'customer', $data);
            }

            if (optional(optional($repliable)->customer)->email) {
                safe_notify(
                    $repliable->customer,
                    new MessageRepliedNotification($event->reply, $repliable->customer->getName()),
                    'message replied customer'
                );
            } elseif (optional($repliable)->email) {
                safe_mail_route_notify(
                    $repliable->email,
                    new MessageRepliedNotification($event->reply, $repliable->name),
                    'message replied guest'
                );
            }
        } elseif ($event->reply->customer_id) {
            // Customer replied → notify shop / assignee
            FCMService::sendToShop(optional($repliable)->shop, [
                'title' => trans('notifications.message_replied.subject', [
                    'user' => optional(optional($repliable)->customer)->getName() ?: 'Customer',
                    'subject' => $subject,
                ]),
                'body' => $preview,
            ], $data);

            if (optional(optional($repliable)->user)->email) {
                safe_notify(
                    $repliable->user,
                    new MessageRepliedNotification($event->reply, $repliable->user->getName()),
                    'message replied user'
                );
            } elseif (config('shop_settings.notify_new_message')) {
                safe_notify(
                    $repliable->shop,
                    new MessageRepliedNotification($event->reply, $repliable->shop->name),
                    'message replied shop'
                );
            }
        }
    }
}
