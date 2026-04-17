<?php

namespace App\Services;

use App\Models\ChatSocketEvent;
use Illuminate\Support\Facades\Log;

class ChatSocketPublisher
{
    public static function publish(string $room, string $event, array $payload): void
    {
        $created = ChatSocketEvent::create([
            'room' => $room,
            'event' => $event,
            'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
        ]);

        if (config('chat_socket.debug')) {
            Log::info('chat_socket.publish', [
                'id' => $created->id,
                'room' => $room,
                'event' => $event,
                'sender_type' => $payload['sender_type'] ?? null,
                'conversation_id' => $payload['conversation_id'] ?? null,
            ]);
        }
    }
}

