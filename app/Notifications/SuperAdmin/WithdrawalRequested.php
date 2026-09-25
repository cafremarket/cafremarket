<?php

namespace App\Notifications\SuperAdmin;

use App\Models\System;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Incevio\Package\Wallet\Models\Transaction;

/**
 * Tells the marketplace admins that a vendor asked to withdraw funds
 * (bank transfer / M-Pesa / eMola) and the request is waiting for approval.
 */
class WithdrawalRequested extends Notification implements ShouldQueue
{
    use Queueable;

    public $tries = 5;

    public $transaction;

    public function __construct(Transaction $transaction)
    {
        $this->transaction = $transaction;
    }

    /**
     * Notify every marketplace admin (super admin + admins).
     */
    public static function notifyAdmins(Transaction $transaction): void
    {
        $system = System::orderBy('id', 'asc')->first();

        foreach ($system ? $system->admins() : [] as $admin) {
            safe_notify($admin, new static($transaction), 'withdrawal requested');
        }
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->from(get_sender_email(), get_sender_name())
            ->subject(trans('notifications.withdrawal_requested.subject', ['shop_name' => $this->shopName()]))
            ->markdown('admin.mail.super_admin.withdrawal_requested', [
                'url' => route('admin.payout.approval', $this->transaction),
                'shop_name' => $this->shopName(),
                'amount' => $this->amount(),
                'payout_method' => $this->payoutMethodLabel(),
                'instruction' => $this->transaction->meta['payout_instruction'] ?? '',
            ]);
    }

    public function toArray($notifiable)
    {
        return [
            'transaction_id' => $this->transaction->id,
            'shop_name' => $this->shopName(),
            'amount' => $this->amount(),
            'payout_method' => $this->transaction->meta['payout_method'] ?? null,
        ];
    }

    private function shopName(): string
    {
        return (string) optional($this->transaction->payable)->name;
    }

    private function amount(): string
    {
        return get_formated_currency(abs($this->transaction->amount), 2, config('system_settings.currency.id'));
    }

    private function payoutMethodLabel(): string
    {
        $method = $this->transaction->meta['payout_method'] ?? null;

        return $method ? trans('packages.wallet.payout_method_'.$method) : '';
    }
}
