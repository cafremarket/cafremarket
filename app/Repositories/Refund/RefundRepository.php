<?php

namespace App\Repositories\Refund;

interface RefundRepository
{
    public function open();

    public function closed();

    public function pending();

    public function completed();

    public function issue();

    public function forTab(string $tab, ?string $search = null);

    public function pendingCount(): int;

    public function statusOf($status);

    public function approve($refund);

    public function decline($refund, ?string $adminNote = null);

    public function markIssue($refund, string $adminNote);

    public function findOrder($order);
}
