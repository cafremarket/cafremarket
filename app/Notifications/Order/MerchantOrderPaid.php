<?php

namespace App\Notifications\Order;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Stored on the shop so the seller app's notification list shows
 * "Payment received — Order #…" (the app reads the shop's database notifications).
 */
class MerchantOrderPaid extends Notification
{
    use Queueable;

    public $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        return [
            'type' => 'order_paid',
            'order' => $this->order->order_number,
            'order_id' => $this->order->id,
            'customer' => (string) optional($this->order->customer)->name,
            'amount' => self::amount($this->order),
            'subject' => self::subject($this->order),
            'message' => self::message($this->order),
        ];
    }

    /**
     * Order number the way chat shows it: "#123456" (never "##123456").
     */
    public static function displayNumber(Order $order): string
    {
        return '#'.ltrim((string) $order->order_number, '#');
    }

    public static function amount(Order $order): string
    {
        try {
            return get_formated_currency($order->grand_total, 2, $order->currency_id);
        } catch (\Throwable $e) {
            return (string) $order->grand_total;
        }
    }

    public static function subject(Order $order): string
    {
        return trans('notifications.merchant_order_paid.subject', ['order' => self::displayNumber($order)]);
    }

    public static function message(Order $order): string
    {
        return trans('notifications.merchant_order_paid.message', [
            'order' => self::displayNumber($order),
            'amount' => self::amount($order),
        ]);
    }
}
