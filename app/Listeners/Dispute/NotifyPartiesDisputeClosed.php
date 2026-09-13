<?php

namespace App\Listeners\Dispute;

use App\Events\Dispute\DisputeClosed;
use App\Notifications\Dispute\Closed as ClosedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyPartiesDisputeClosed implements ShouldQueue
{
    public $tries = 5;

    public function handle(DisputeClosed $event): void
    {
        try {
            if ($event->dispute->customer) {
                safe_notify($event->dispute->customer, new ClosedNotification($event->dispute), 'dispute closed customer');
            }

            if ($event->dispute->shop) {
                safe_notify($event->dispute->shop, new ClosedNotification($event->dispute), 'dispute closed merchant');
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
