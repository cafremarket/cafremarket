<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * LiveChat is loaded in production, but LiveChatServiceProvider never
 * registered package migrations, so `chat_conversations` was never created.
 * Vendor/app chat then 500s: Table 'cafremarket.chat_conversations' doesn't exist.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('chat_conversations')) {
            Schema::create('chat_conversations', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('shop_id')->nullable();
                $table->unsignedBigInteger('customer_id')->nullable();
                $table->unsignedBigInteger('order_id')->nullable()->index();
                $table->text('message')->nullable();
                $table->integer('status')->default(1);
                $table->boolean('private')->default(true);
                $table->boolean('read')->nullable();
                $table->softDeletes();
                $table->timestamps();

                if (Schema::hasTable('shops')) {
                    $table->foreign('shop_id')->references('id')->on('shops')->onDelete('set null');
                }
                if (Schema::hasTable('customers')) {
                    $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
                }
            });

            return;
        }

        if (! Schema::hasColumn('chat_conversations', 'order_id')) {
            Schema::table('chat_conversations', function (Blueprint $table) {
                $table->unsignedBigInteger('order_id')->nullable()->after('customer_id');
                $table->index('order_id');
            });
        }
    }

    public function down(): void
    {
        // Do not drop on rollback — production may already have chat rows.
    }
};
