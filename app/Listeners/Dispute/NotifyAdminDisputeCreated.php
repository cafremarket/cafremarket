<?php

namespace App\Listeners\Dispute;

use App\Events\Dispute\DisputeCreated;
use App\Models\System;
use App\Notifications\Dispute\Created as DisputeCreatedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyAdminDisputeCreated implements ShouldQueue
{
    public $tries = 5;

    public function handle(DisputeCreated $event): void
    {
        try {
            $system = System::orderBy('id', 'asc')->first();
            $admin = $system?->superAdmin();
            if ($admin) {
                safe_notify($admin, new DisputeCreatedNotification($event->dispute), 'dispute ticket admin');
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
