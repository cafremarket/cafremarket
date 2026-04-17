<?php

namespace App\Listeners\Chat;

use App\Events\Chat\NewMessageEvent;
use App\Notifications\Chat\NewMessage;

class NotifyAssociatedUsers
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
    public function handle(NewMessageEvent $event)
    {
        $repliable = $event->msg_obj->repliable ?? $event->msg_obj;

        // if (! config('system_settings')) {
        //     setSystemConfig($repliable->shop_id);
        // }

        // // Set shop configuration
        // if ($repliable->shop_id && !config('shop_settings')) {
        //     setSystemConfig($repliable->shop_id);
        // }

        // \Log::info("repliable: ");
        // \Log::info($repliable);

        if ($event->msg_obj->customer_id) {
            $associate = $repliable->shop;
            // $associate = $repliable->agent ?? $repliable->shop;
            $sender = $repliable->customer->getName();
            $receipent = $associate->getName();

            try {
                $associate->notify(new NewMessage($receipent, $sender, $event->text, $repliable));
            } catch (\Throwable $e) {
                report($e);
            }
        }
        // else {
        //     $associate = $repliable->customer;
        //     $sender = $repliable->shop->getName();
        // }

        // $receipent = $associate->getName();
        // if ($receipent) {
        //     $associate->notify(new NewMessage($receipent, $sender, $event->text, $repliable));
        // }
    }
}
