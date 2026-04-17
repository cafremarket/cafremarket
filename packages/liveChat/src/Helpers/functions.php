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
