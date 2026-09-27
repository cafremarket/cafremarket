<?php

namespace App\Services\Affiliate;

use App\Models\Cancellation;
use App\Models\Dispute;
use App\Models\Order;
use App\Models\Refund;
use App\Services\Orders\RefundWindow;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Incevio\Package\Affiliate\Models\AffiliateCommission;

/**
 * Affiliate commissions are credited only once the refund/return period of the
 * affiliate's item has ended (RefundWindow), so refunded sales never pay out.
 *
 * - On delivery: schedule() sets release_at (delivery + the item's refund days).
 * - Hourly: releaseDue() pays what is due; orders with an open refund request,
 *   return request or dispute wait until it is settled.
 * - Refunded/canceled orders void the commission; partial refunds reduce it. Either way
 *   the part the vendor had already given up at settlement goes back to the vendor.
 */
class AffiliateCommissionRelease
{
    /** Set when each unpaid commission of this delivered order becomes payable. */
    public function schedule(Order $order): void
    {
        if (! $this->enabled()) {
            return;
        }

        $order->loadMissing(['affiliateCommissions', 'inventories']);

        foreach ($order->affiliateCommissions as $commission) {
            if ($commission->isPaid() || $commission->isVoided()) {
                continue;
            }

            $releaseAt = $this->releaseDate($order, $commission);

            if ($releaseAt) {
                $commission->forceFill(['release_at' => $releaseAt])->save();
            }
        }
    }

    /**
     * Pay, hold or void every commission whose refund period has ended.
     *
     * @return array{paid: int, voided: int, held: int, failed: int}
     */
    public function releaseDue(?Carbon $now = null): array
    {
        $stats = ['paid' => 0, 'voided' => 0, 'held' => 0, 'failed' => 0];

        if (! $this->enabled()) {
            return $stats;
        }

        $now = $now ?? now();

        $this->scheduleMissing();

        AffiliateCommission::query()
            ->where('paid', false)
            ->whereNull('voided_at')
            ->whereNotNull('release_at')
            ->where('release_at', '<=', $now)
            ->with(['order.refunds', 'order.dispute', 'order.cancellation', 'affiliate'])
            ->orderBy('id')
            ->chunkById(100, function ($commissions) use (&$stats) {
                foreach ($commissions as $commission) {
                    try {
                        $stats[$this->settle($commission)]++;
                    } catch (\Throwable $e) {
                        $stats['failed']++;
                        Log::channel('wallet')->error('Affiliate commission #'.$commission->id.' release failed: '.$e->getMessage());
                    }
                }
            });

        return $stats;
    }

    /** @return 'paid'|'voided'|'held' */
    public function settle(AffiliateCommission $commission): string
    {
        $order = $commission->order;

        if (! $order) {
            return 'held';
        }

        if ($this->isFullyReversed($order)) {
            DB::transaction(function () use ($commission, $order) {
                $this->returnToVendor($order, (float) $commission->total_commission, $commission);
                $commission->forceFill(['voided_at' => now()])->save();
            });

            return 'voided';
        }

        if ($this->hasOpenClaim($order)) {
            return 'held';
        }

        DB::transaction(function () use ($commission, $order) {
            $refunded = (float) $order->refunds->where('status', Refund::STATUS_APPROVED)->sum('amount');
            $total = (float) $order->grand_total;

            if ($refunded > 0 && $total > 0) {
                $keepShare = max(0, 1 - min(1, $refunded / $total));
                $original = (float) $commission->total_commission;
                $reduced = round($original * $keepShare, 2);

                $this->returnToVendor($order, round($original - $reduced, 2), $commission);
                $commission->total_commission = $reduced;
                $commission->save();
            }

            if ((float) $commission->total_commission > 0) {
                $commission->markAsPaid();
            } else {
                $commission->forceFill(['voided_at' => now()])->save();
            }
        });

        return $commission->isVoided() ? 'voided' : 'paid';
    }

    /** When the commission may be paid: delivery + refund days of its item (0 days = at delivery). */
    public function releaseDate(Order $order, AffiliateCommission $commission): ?Carbon
    {
        $deliveredAt = RefundWindow::deliveredAt($order);

        if (! $deliveredAt) {
            return null;
        }

        $item = $order->inventories->firstWhere('id', (int) $commission->inventory_id);

        $days = $item
            ? RefundWindow::daysFor($item)
            : (int) $order->inventories->map(fn ($i) => RefundWindow::daysFor($i))->max();

        return $days > 0 ? $deliveredAt->copy()->addDays($days)->endOfDay() : $deliveredAt->copy();
    }

    /** Delivered orders whose unpaid commission has no release date yet (e.g. delivered before this change). */
    protected function scheduleMissing(): void
    {
        AffiliateCommission::query()
            ->where('paid', false)
            ->whereNull('voided_at')
            ->whereNull('release_at')
            ->whereHas('order', fn ($q) => $q->where('order_status_id', '>=', Order::STATUS_DELIVERED))
            ->with('order')
            ->chunkById(100, function ($commissions) {
                foreach ($commissions as $commission) {
                    $this->schedule($commission->order);
                }
            });
    }

    protected function isFullyReversed(Order $order): bool
    {
        return (int) $order->payment_status === Order::PAYMENT_STATUS_REFUNDED
            || in_array((int) $order->order_status_id, [Order::STATUS_CANCELED, Order::STATUS_RETURNED], true);
    }

    /** A refund request, return request or dispute still waiting for a decision. */
    protected function hasOpenClaim(Order $order): bool
    {
        if ($order->refunds->contains('status', Refund::STATUS_NEW)) {
            return true;
        }

        $dispute = $order->dispute;
        if ($dispute && in_array((int) $dispute->status, [
            Dispute::STATUS_NEW, Dispute::STATUS_OPEN, Dispute::STATUS_WAITING,
            Dispute::STATUS_APPEALED, Dispute::STATUS_CLOSE_REQUESTED,
        ], true)) {
            return true;
        }

        $cancellation = $order->cancellation;

        return $cancellation && $cancellation->return_goods
            && in_array((int) $cancellation->status, [Cancellation::STATUS_NEW, Cancellation::STATUS_OPEN], true);
    }

    /**
     * Give the vendor back affiliate commission that was withheld from their sale
     * credit but will no longer be paid to the affiliate.
     */
    protected function returnToVendor(Order $order, float $amount, AffiliateCommission $commission): void
    {
        $shop = $order->shop;

        if ($amount <= 0 || ! $shop || ! is_incevio_package_loaded('wallet')) {
            return;
        }

        $saleCredit = $shop->transactions()
            ->where('type', 'deposit')
            ->where('meta->order_id', $order->id)
            ->get()
            ->first(fn ($t) => (float) $t->getFromMetaData('affiliate_commission') > 0);

        if (! $saleCredit) {
            return; // never settled: nothing was withheld from the vendor
        }

        $amount = min($amount, (float) $saleCredit->getFromMetaData('affiliate_commission'));

        $shop->deposit(round($amount, 2), [
            'type' => trans('packages.wallet.affiliate_return'),
            'purpose' => 'affiliate_return',
            'description' => trans('packages.wallet.affiliate_return_of', ['order' => $order->order_number]),
            'affiliate_return' => round($amount, 2),
            'affiliate_return_order_id' => $order->id,
            'affiliate_commission_id' => $commission->id,
        ], true);
    }

    protected function enabled(): bool
    {
        return is_incevio_package_loaded('affiliate') && is_incevio_package_loaded('wallet');
    }
}
