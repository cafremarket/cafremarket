<?php

namespace App\Listeners\Dispute;

use App\Events\Dispute\DisputeCloseRequested;
use App\Models\System;
use App\Notifications\Dispute\CloseRequested as CloseRequestedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyAdminCloseRequested implements ShouldQueue
{
    public $tries = 5;

    public function handle(DisputeCloseRequested $event): void
    {
        try {
            $system = System::orderBy('id', 'asc')->first();
            $admin = $system?->superAdmin();
            if ($admin) {
                safe_notify($admin, new CloseRequestedNotification($event->dispute), 'dispute close requested admin');
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
