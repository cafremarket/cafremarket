<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class ChatSocketEvent extends BaseModel
{
    use HasFactory;

    protected $table = 'chat_socket_events';

    protected $fillable = [
        'room',
        'event',
        'payload',
    ];
}

