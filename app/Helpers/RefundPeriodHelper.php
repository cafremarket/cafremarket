<?php

if (! function_exists('refund_period_label')) {
    /** "7 days refund/return" or "No refund/return". */
    function refund_period_label($days): string
    {
        $days = (int) $days;

        return $days > 0
            ? trans_choice('refund_period.days_policy', $days, ['count' => $days])
            : trans('refund_period.no_refund');
    }
}

if (! function_exists('variant_refund_days')) {
    /**
     * Refund period sent for one variant row (variant_refund_days[key]); an empty
     * choice ("Same as product") falls back to the product's refund_days, then to $default.
     */
    function variant_refund_days(\Illuminate\Http\Request $request, $key, $default = null)
    {
        $value = ((array) $request->input('variant_refund_days', []))[$key] ?? null;

        if ($value !== null && $value !== '') {
            return $value;
        }

        return $request->filled('refund_days') ? $request->input('refund_days') : $default;
    }
}
