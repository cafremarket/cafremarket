<?php

if (! function_exists('report_money')) {
    /** Amount in the platform currency, for report pages. */
    function report_money(float|int|string|null $value): string
    {
        return get_formated_currency((float) $value, 2, config('system_settings.currency.id'));
    }
}

if (! function_exists('report_percent')) {
    function report_percent(float|int|null $value, int $decimals = 1): string
    {
        return number_format((float) $value, $decimals).'%';
    }
}

if (! function_exists('report_date')) {
    function report_date($value, bool $withTime = false): string
    {
        if (empty($value)) {
            return '—';
        }

        $date = $value instanceof \Carbon\Carbon ? $value : \Carbon\Carbon::parse($value);

        return $withTime ? $date->format('Y-m-d H:i') : $date->format('Y-m-d');
    }
}
