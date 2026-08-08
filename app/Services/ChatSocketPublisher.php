<?php

namespace App\Services;

use App\Models\ChatSocketEvent;
use Illuminate\Support\Facades\Log;

class ChatSocketPublisher
{
    public static function publish(string $room, string $event, array $payload): void
    {
        $t0 = microtime(true);
        $payload['_published_at'] = now()->toIso8601String();
        $payload['_published_ms'] = (int) round($t0 * 1000);

        $created = ChatSocketEvent::create([
            'room' => $room,
            'event' => $event,
            'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
        ]);

        $elapsedMs = (int) round((microtime(true) - $t0) * 1000);

        // Always log publish timing while diagnosing chat delay.
        // Turn off later with CHAT_SOCKET_DEBUG=false in .env
        if (config('chat_socket.debug', true)) {
            Log::info('chat_socket.publish', [
                'id' => $created->id,
                'room' => $room,
                'event' => $event,
                'sender_type' => $payload['sender_type'] ?? null,
                'conversation_id' => $payload['conversation_id'] ?? null,
                'reply_id' => $payload['reply_id'] ?? null,
                'db_insert_ms' => $elapsedMs,
                'published_ms' => $payload['_published_ms'],
            ]);
        }
    }
}
