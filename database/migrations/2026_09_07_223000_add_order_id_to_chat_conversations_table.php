<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('chat_conversations')) {
            return;
        }

        Schema::table('chat_conversations', function (Blueprint $table) {
            if (! Schema::hasColumn('chat_conversations', 'order_id')) {
                $table->unsignedBigInteger('order_id')->nullable()->after('customer_id');
                $table->index('order_id');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('chat_conversations')) {
            return;
        }

        Schema::table('chat_conversations', function (Blueprint $table) {
            if (Schema::hasColumn('chat_conversations', 'order_id')) {
                $table->dropIndex(['order_id']);
                $table->dropColumn('order_id');
            }
        });
    }
};
