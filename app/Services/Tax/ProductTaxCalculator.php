<?php

namespace App\Services\Tax;

use App\Models\Cart;
use App\Models\Inventory;
use App\Models\Tax;

/**
 * Product-level taxes (fixed | percent), summed across cart lines — same pattern as shipping.
 */
class ProductTaxCalculator
{
    /**
     * Whether any cart line has product taxes assigned.
     */
    public function cartUsesProductTaxes(Cart $cart): bool
    {
        $cart->loadMissing(['inventories.product.taxes']);

        foreach ($cart->inventories as $item) {
            if ($item->resolvedProductTaxes()->isNotEmpty()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Apply calculated product taxes onto the cart (mutates, does not save).
     */
    public function applyToCart(Cart $cart): Cart
    {
        $result = $this->calculateForCart($cart);
        $cart->taxes = round((float) $result['amount'], config('system_settings.decimals', 2));

        return $cart;
    }

    /**
     * @return array{amount: float, uses_product_taxes: bool, items: array, lines: array}
     */
    public function calculateForCart(Cart $cart): array
    {
        $cart->loadMissing(['inventories.product.taxes', 'shop']);

        $itemRows = [];
        $lines = [];
        $sum = 0.0;
        $usesProductTaxes = false;

        foreach ($cart->inventories as $item) {
            $qty = max(1, (int) ($item->pivot->quantity ?? 1));
            $unitPrice = (float) ($item->pivot->unit_price ?? 0);
            $lineResult = $this->calculateForItem($item, $qty, $unitPrice);

            if (! empty($lineResult['taxes'])) {
                $usesProductTaxes = true;
            }

            $sum += (float) $lineResult['amount'];
            $itemRows[] = $lineResult;
            foreach ($lineResult['taxes'] as $taxLine) {
                $lines[] = $taxLine;
            }
        }

        return [
            'amount' => round($sum, 6),
            'uses_product_taxes' => $usesProductTaxes,
            'items' => $itemRows,
            'lines' => $lines,
        ];
    }

    /**
     * @return array{inventory_id: int, title: string, quantity: int, amount: float, taxes: array}
     */
    public function calculateForItem(Inventory $item, int $qty, float $unitPrice): array
    {
        $qty = max(1, $qty);
        $taxes = $item->resolvedProductTaxes();
        $taxLines = [];
        $amount = 0.0;

        $title = $item->pivot->item_description
            ?? $item->title
            ?? ('#'.$item->id);

        foreach ($taxes as $tax) {
            $charge = $this->chargeForTax($tax, $qty, $unitPrice);
            $amount += $charge;
            $taxLines[] = [
                'inventory_id' => (int) $item->id,
                'product_id' => (int) ($item->product_id ?? 0),
                'tax_id' => (int) $tax->id,
                'tax_name' => (string) $tax->name,
                'tax_type' => $tax->isFixed() ? Tax::TYPE_FIXED : Tax::TYPE_PERCENT,
                'taxrate' => (float) $tax->taxrate,
                'title' => strip_tags((string) $title),
                'quantity' => $qty,
                'amount' => round($charge, 6),
                'shop_name' => optional($item->shop)->name ?? optional($item->product?->shop)->name,
            ];
        }

        return [
            'inventory_id' => (int) $item->id,
            'title' => strip_tags((string) $title),
            'quantity' => $qty,
            'amount' => round($amount, 6),
            'taxes' => $taxLines,
        ];
    }

    public function chargeForTax(Tax $tax, int $qty, float $unitPrice): float
    {
        $qty = max(1, $qty);
        $rate = max(0.0, (float) $tax->taxrate);

        if ($tax->isFixed()) {
            return round($rate * $qty, 6);
        }

        $lineTotal = max(0.0, $unitPrice) * $qty;

        return round($lineTotal * ($rate / 100), 6);
    }

    /**
     * API / UI friendly breakdown rows.
     *
     * @return array<int, array{inventory_id: ?int, title: string, tax_name: string, tax_type: string, quantity: int, amount: string, amount_raw: string, shop_name: ?string}>
     */
    public function breakdownForCart(Cart $cart): array
    {
        $decimal = config('system_settings.decimals', 2);
        $result = $this->calculateForCart($cart);
        $rows = [];

        foreach ($result['lines'] as $line) {
            $amount = (float) ($line['amount'] ?? 0);
            $rows[] = [
                'inventory_id' => $line['inventory_id'] ?? null,
                'title' => $line['title'] ?? '',
                'tax_name' => $line['tax_name'] ?? '',
                'tax_type' => $line['tax_type'] ?? Tax::TYPE_PERCENT,
                'quantity' => (int) ($line['quantity'] ?? 1),
                'amount' => get_formated_currency($amount, $decimal),
                'amount_raw' => strval(round($amount, 2)),
                'shop_name' => $line['shop_name'] ?? null,
            ];
        }

        return $rows;
    }
}
