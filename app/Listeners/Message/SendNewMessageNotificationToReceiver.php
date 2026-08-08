<?php

namespace App\Listeners\Message;

use App\Events\Message\NewMessage;
use App\Models\Message;
use App\Models\System;
use App\Notifications\Message\NewMessage as NewMessageNotification;
use App\Services\FCMService;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendNewMessageNotificationToReceiver implements ShouldQueue
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
    public function handle(NewMessage $event)
    {
        if (! config('system_settings')) {
            setSystemConfig();
        }

        // Set shop configuration
        if ($event->message->shop_id && ! config('shop_settings')) {
            setSystemConfig($event->message->shop_id);
        }

        $customer_token = optional($event->message->customer)->fcm_token;

        if (! is_null($customer_token)) {
            FCMService::send($customer_token, [
                'title' => trans('notifications.new_message.subject', ['subject' => $event->message->subject]),
                'body' => trans('notifications.new_message.message', ['message' => $event->message->message]),
            ]);
        }

        if ($event->message->label == Message::LABEL_INBOX) {
            if ($event->message->shop_id) {
                if (config('shop_settings.notify_new_message')) {
                    safe_notify($event->message->shop, new NewMessageNotification($event->message, $event->message->shop->name), 'new message shop');
                }
            } elseif (config('system_settings.notify_new_message')) {
                $system = System::orderBy('id', 'asc')->first();
                safe_notify($system, new NewMessageNotification($event->message, $system->superAdmin->getName()), 'new message system');
            }
        } elseif ($event->message->label == Message::LABEL_SENT) {
            if ($event->message->order_id && $event->message->email) {
                safe_mail_route_notify(
                    $event->message->email,
                    new NewMessageNotification($event->message, trans('app.guest_customer'), true),
                    'new message guest'
                );
            } else {
                safe_notify($event->message->customer, new NewMessageNotification($event->message, $event->message->customer->getName()), 'new message customer');
            }
        }
    }
}
