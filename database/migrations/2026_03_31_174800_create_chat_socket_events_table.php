<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_socket_events', function (Blueprint $table) {
            $table->id();
            $table->string('room', 191)->index();
            $table->string('event', 100)->default('chat.message');
            $table->longText('payload');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_socket_events');
    }
};

