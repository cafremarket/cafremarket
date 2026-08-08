<?php

namespace App\Listeners\Message;

use App\Events\Message\MessageReplied;
use App\Notifications\Message\Replied as MessageRepliedNotification;
use App\Services\FCMService;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyAssociatedUsersMessagetReplied implements ShouldQueue
{
    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 5;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

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

        // Set shop configuration
        if ($event->reply->repliable && $event->reply->repliable->shop_id && ! config('shop_settings')) {
            setSystemConfig($event->reply->repliable->shop_id);
        }

        $customer_token = optional($event->reply->customer)->fcm_token;

        if (! is_null($customer_token)) {
            FCMService::send($customer_token, [
                'title' => trans('notifications.message_replied.subject', ['user' => $event->reply->user->getName(), 'subject' => $event->reply->repliable->subject]),
                'body' => trans('notifications.new_message.message', ['message' => $event->reply->reply]),
            ]);
        }

        if ($event->reply->user_id) {
            if ($event->reply->repliable->customer->email) {
                safe_notify(
                    $event->reply->repliable->customer,
                    new MessageRepliedNotification($event->reply, $event->reply->repliable->customer->getName()),
                    'message replied customer'
                );
            } elseif ($event->reply->repliable->email) {
                safe_mail_route_notify(
                    $event->reply->repliable->email,
                    new MessageRepliedNotification($event->reply, $event->reply->repliable->name),
                    'message replied guest'
                );
            }
        } elseif ($event->reply->customer_id) {
            if ($event->reply->repliable->user->email) {
                safe_notify(
                    $event->reply->repliable->user,
                    new MessageRepliedNotification($event->reply, $event->reply->repliable->user->getName()),
                    'message replied user'
                );
            } elseif (config('shop_settings.notify_new_message')) {
                safe_notify(
                    $event->reply->repliable->shop,
                    new MessageRepliedNotification($event->reply, $event->reply->repliable->shop->name),
                    'message replied shop'
                );
            }
        }
    }
}
