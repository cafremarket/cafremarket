<?php

if (! function_exists('livechat_message_for_attachment_only')) {
    /**
     * Stored reply/message body when the user sends a file with no caption.
     */
    function livechat_message_for_attachment_only(): string
    {
        return '[attachment]';
    }
}

if (! function_exists('livechat_is_merchant_panel')) {
    /**
     * Whether the current request is the merchant store panel.
     */
    function livechat_is_merchant_panel(): bool
    {
        $request = request();

        return $request->is('merchant/*')
            || $request->routeIs('merchant.*')
            || (bool) $request->attributes->get('merchant_panel');
    }
}

if (! function_exists('livechat_support_route_name')) {
    /**
     * Admin or merchant support chat route name.
     */
    function livechat_support_route_name(string $name): string
    {
        $prefix = livechat_is_merchant_panel() ? 'merchant.support.' : 'admin.support.';

        return $prefix.$name;
    }
}

if (! function_exists('livechat_support_route')) {
    /**
     * Generate a support chat URL for the active panel.
     *
     * @param  mixed  $parameters
     */
    function livechat_support_route(string $name, $parameters = [], bool $absolute = true): string
    {
        return route(livechat_support_route_name($name), $parameters, $absolute);
    }
}

if (! function_exists('livechat_format_message_time')) {
    /**
     * Clock time for a chat bubble (e.g. 10:45 AM).
     *
     * @param  \DateTimeInterface|string|null  $datetime
     */
    function livechat_format_message_time($datetime): string
    {
        if (! $datetime) {
            return '';
        }

        try {
            $dt = \Illuminate\Support\Carbon::parse($datetime)->timezone(config('app.timezone'));
        } catch (\Throwable $e) {
            return '';
        }

        return $dt->format('g:i A');
    }
}

if (! function_exists('livechat_format_day_label')) {
    /**
     * Day separator label: Today / Yesterday / Mon, Sep 4, 2026.
     *
     * @param  \DateTimeInterface|string|null  $datetime
     */
    function livechat_format_day_label($datetime): string
    {
        if (! $datetime) {
            return '';
        }

        try {
            $dt = \Illuminate\Support\Carbon::parse($datetime)->timezone(config('app.timezone'));
        } catch (\Throwable $e) {
            return '';
        }

        if ($dt->isToday()) {
            $label = trans('theme.today');

            return ($label && $label !== 'theme.today') ? (string) $label : 'Today';
        }

        if ($dt->isYesterday()) {
            $label = trans('theme.yesterday');

            return ($label && $label !== 'theme.yesterday') ? (string) $label : 'Yesterday';
        }

        return $dt->format('D, M j, Y');
    }
}

if (! function_exists('livechat_day_key')) {
    /**
     * Y-m-d key for grouping messages by calendar day.
     *
     * @param  \DateTimeInterface|string|null  $datetime
     */
    function livechat_day_key($datetime): string
    {
        if (! $datetime) {
            return '';
        }

        try {
            return \Illuminate\Support\Carbon::parse($datetime)->timezone(config('app.timezone'))->toDateString();
        } catch (\Throwable $e) {
            return '';
        }
    }
}

if (! function_exists('livechat_socket_attachments_payload')) {
    /**
     * Attachment rows for WebSocket clients (admin + storefront) so images show without full reload.
     *
     * @param  \Illuminate\Database\Eloquent\Model|null  $attachable
     */
    function livechat_socket_attachments_payload($attachable): array
    {
        if (! $attachable) {
            return [];
        }

        $attachable->loadMissing('attachments');

        if (! $attachable->attachments || $attachable->attachments->isEmpty()) {
            return [];
        }

        return $attachable->attachments->map(function ($a) {
            return [
                'path' => $a->path,
                'name' => $a->name,
                'extension' => $a->extension,
                'url' => get_storage_file_url($a->path),
            ];
        })->values()->all();
    }
}
