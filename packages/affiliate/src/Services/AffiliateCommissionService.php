<?php

namespace Incevio\Package\Affiliate\Services;

use App\Models\Order;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Incevio\Package\Affiliate\Models\AffiliateCommission;
use Incevio\Package\Affiliate\Models\AffiliateLink;

class AffiliateCommissionService
{
    public function __construct(protected AffiliateAttributionService $attribution)
    {
    }

    /**
     * Credit at most one affiliate for an order:
     * last clicked link wins, the clicked product must be in the order,
     * the click must be within 7 days, and this customer is paid once
     * per window. After that window ends, a new click starts 7 days again.
     */
    public function applyToOrder(Order $order, $items): ?AffiliateCommission
    {
        if (! is_incevio_package_loaded('affiliate')) {
            return null;
        }

        $this->attribution->captureFromRequest();

        return DB::transaction(function () use ($order, $items) {
            $existing = AffiliateCommission::where('order_id', $order->id)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                $this->syncOrderAmounts($order, $existing);

                return $existing;
            }

            $click = $this->attribution->current();

            if (! $click) {
                return null;
            }

            $items = $items instanceof Collection ? $items : collect($items);
            $item = $items->firstWhere('id', (int) $click['inventory_id']);

            if (! $item || ! $item->hasAffiliateCommission()) {
                return null;
            }

            if ($this->previousWindowStillBlocks($order, (int) $item->id, $click)) {
                return null;
            }

            $link = AffiliateLink::where('id', (int) $click['affiliate_link_id'])
                ->where('affiliate_id', (int) $click['affiliate_id'])
                ->where('inventory_id', (int) $item->id)
                ->first();

            if (! $link) {
                $link = AffiliateLink::where('affiliate_id', (int) $click['affiliate_id'])
                    ->where('inventory_id', (int) $item->id)
                    ->first();
            }

            if (! $link) {
                return null;
            }

            $quantity = (float) data_get($item, 'pivot.quantity', 1);
            $unitPrice = (float) data_get($item, 'pivot.unit_price', $item->current_sale_price());
            $rate = (float) $item->affiliates_percentage;
            $total = round($unitPrice * $quantity * ($rate / 100), 2);

            if ($total <= 0) {
                return null;
            }

            $payload = [
                'affiliate_id' => $link->affiliate_id,
                'affiliate_link_id' => $link->id,
                'inventory_id' => $item->id,
                'order_id' => $order->id,
                'commission_rate' => $rate,
                'total_commission' => $total,
            ];

            if (Schema::hasColumn('affiliate_commissions', 'customer_id')) {
                $payload['customer_id'] = $order->customer_id;
            }

            if (Schema::hasColumn('affiliate_commissions', 'customer_email')) {
                $payload['customer_email'] = $this->normalizeEmail($order->email);
            }

            if (Schema::hasColumn('affiliate_commissions', 'clicked_at')) {
                $payload['clicked_at'] = $click['clicked_at'];
            }

            try {
                $commission = AffiliateCommission::create($payload);
            } catch (QueryException $e) {
                $existing = AffiliateCommission::where('order_id', $order->id)->first();
                if ($existing) {
                    $this->syncOrderAmounts($order, $existing);

                    return $existing;
                }

                throw $e;
            }

            $link->increment('order_count', (int) $quantity);

            $this->syncOrderAmounts($order, $commission);

            if (Schema::hasColumn('order_items', 'affiliate_commission_amount')) {
                DB::table('order_items')
                    ->where('order_id', $order->id)
                    ->where('inventory_id', $item->id)
                    ->update(['affiliate_commission_amount' => $total]);
            }

            $this->attribution->markProductConverted((int) $item->id, (string) $click['clicked_at']);

            return $commission;
        });
    }

    public function amountForOrder(Order $order): float
    {
        $stored = max(0, round((float) ($order->affiliate_commission_amount ?? 0), 2));

        if ($stored > 0) {
            return $stored;
        }

        if (! is_incevio_package_loaded('affiliate')) {
            return 0.0;
        }

        return max(0, round((float) $order->affiliateCommissions()->sum('total_commission'), 2));
    }

    /**
     * Block a second commission while the previous 7-day window is still open.
     * A click made after that window ends starts a new payable period.
     */
    protected function previousWindowStillBlocks(Order $order, int $inventoryId, array $click): bool
    {
        if (! $this->attribution->canEarnForClick($click)) {
            return true;
        }

        $latest = $this->latestCommissionForCustomer($order, $inventoryId);

        if (! $latest) {
            return false;
        }

        $anchor = $latest->clicked_at ?: $latest->created_at;

        try {
            $windowEnd = \Carbon\Carbon::parse($anchor)->addDays($this->attribution->attributionDays());
            $clickedAt = \Carbon\Carbon::parse($click['clicked_at']);
        } catch (\Throwable $e) {
            return true;
        }

        return $clickedAt->lt($windowEnd);
    }

    protected function latestCommissionForCustomer(Order $order, int $inventoryId): ?AffiliateCommission
    {
        $customerId = (int) ($order->customer_id ?? 0);
        $email = $this->normalizeEmail($order->email);

        if ($customerId < 1 && $email === '') {
            return null;
        }

        return AffiliateCommission::query()
            ->where('inventory_id', $inventoryId)
            ->where(function ($q) use ($order) {
                $q->whereNull('order_id')
                    ->orWhere('order_id', '!=', $order->id);
            })
            ->where(function ($q) use ($customerId, $email) {
                if ($customerId > 0 && Schema::hasColumn('affiliate_commissions', 'customer_id')) {
                    $q->orWhere('customer_id', $customerId);
                }

                if ($email !== '' && Schema::hasColumn('affiliate_commissions', 'customer_email')) {
                    $q->orWhere('customer_email', $email);
                }

                $q->orWhereHas('order', function ($orderQuery) use ($customerId, $email) {
                    $orderQuery->where(function ($inner) use ($customerId, $email) {
                        if ($customerId > 0) {
                            $inner->orWhere('customer_id', $customerId);
                        }

                        if ($email !== '') {
                            $inner->orWhereRaw('LOWER(email) = ?', [$email]);
                        }

                        if ($customerId < 1 && $email === '') {
                            $inner->whereRaw('1 = 0');
                        }
                    });
                });
            })
            ->latest('id')
            ->first();
    }

    protected function syncOrderAmounts(Order $order, AffiliateCommission $commission): void
    {
        $order->forceFill([
            'affiliate_id' => $commission->affiliate_id,
            'affiliate_commission_amount' => round((float) $commission->total_commission, 2),
        ])->save();
    }

    protected function normalizeEmail(?string $email): string
    {
        return strtolower(trim((string) $email));
    }
}
