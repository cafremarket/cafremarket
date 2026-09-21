<?php

if (! function_exists('livechat_linkify_html')) {
    /**
     * Escapes a plain chat message, then wraps bare URLs in a real link —
     * safe against XSS since the regex only ever runs against already-
     * escaped text. Mirrors the client-side `linkify()` used for
     * client-rendered (realtime/optimistic) bubbles so first-paint and
     * realtime bubbles look identical.
     */
    function livechat_linkify_html(?string $text): string
    {
        $escaped = e((string) $text);

        return preg_replace('/(https?:\/\/[^\s<]+)/', '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>', $escaped);
    }
}

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

if (! function_exists('livechat_order_share_prefix')) {
    function livechat_order_share_prefix(): string
    {
        return '[order_share]';
    }
}

if (! function_exists('livechat_build_order_share_payload')) {
    /**
     * @return array<string, mixed>
     */
    function livechat_build_order_share_payload(\App\Models\Order $order): array
    {
        return \App\Services\OrderChatSyncService::buildOrderSharePayload($order);
    }
}

if (! function_exists('livechat_build_order_share_message')) {
    function livechat_build_order_share_message(\App\Models\Order $order): string
    {
        return \App\Services\OrderChatSyncService::buildOrderShareMessage($order);
    }
}

if (! function_exists('livechat_replies_eager_load')) {
    /**
     * Eager-load relations for chat reply bubbles (attachments + quoted parent).
     *
     * @return array<int, string>
     */
    function livechat_replies_eager_load(): array
    {
        $rels = ['replies.attachments'];
        if (\Illuminate\Support\Facades\Schema::hasColumn('replies', 'parent_id')) {
            $rels[] = 'replies.parent';
        }

        return $rels;
    }
}

if (! function_exists('livechat_quoted_snippet')) {
    /**
     * Short plain-text preview of a chat message for quote-reply UI.
     */
    function livechat_quoted_snippet(?string $text, int $max = 80): string
    {
        $raw = trim((string) $text);
        if ($raw === '') {
            return '';
        }

        if (str_starts_with($raw, '[product_share]')) {
            $share = json_decode(substr($raw, strlen('[product_share]')), true);
            $title = is_array($share) ? (string) ($share['title'] ?? '') : '';

            return $title !== '' ? $title : 'Product';
        }

        if (str_starts_with($raw, '[order_share]')) {
            $share = json_decode(substr($raw, strlen('[order_share]')), true);
            if (is_array($share)) {
                $title = (string) ($share['title'] ?? '');
                if ($title !== '') {
                    return $title;
                }
                $number = (string) ($share['order_number'] ?? '');
                if ($number !== '') {
                    return 'Order #'.$number;
                }
            }

            return 'Order';
        }

        if ($raw === '[attachment]' || strcasecmp($raw, '[attachment]') === 0) {
            $label = trans('theme.attachment');

            return ($label && $label !== 'theme.attachment') ? (string) $label : 'Attachment';
        }

        $plain = trim(preg_replace('/\s+/', ' ', strip_tags($raw)) ?? '');
        if ($plain === '') {
            return '';
        }

        $len = function_exists('mb_strlen') ? mb_strlen($plain) : strlen($plain);
        if ($len > $max) {
            return (function_exists('mb_substr') ? mb_substr($plain, 0, $max) : substr($plain, 0, $max)).'…';
        }

        return $plain;
    }
}

if (! function_exists('livechat_quote_socket_payload')) {
    /**
     * parent_id + quoted_reply fields for WebSocket clients.
     *
     * @return array<string, mixed>
     */
    function livechat_quote_socket_payload(?\App\Models\Reply $parent): array
    {
        return [
            'parent_id' => $parent?->id,
            'quoted_reply' => \App\Models\Reply::quoteSnapshot($parent),
        ];
    }
}
