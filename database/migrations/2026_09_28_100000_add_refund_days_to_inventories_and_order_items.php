<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-listing refund/return period: 0 = no refund/return, otherwise 1-15 days
 * after delivery. Order items keep the value that applied when the order was placed.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('inventories', 'refund_days')) {
            Schema::table('inventories', function (Blueprint $table) {
                $table->unsignedTinyInteger('refund_days')->default(7)->after('min_order_quantity');
            });
        }

        if (! Schema::hasColumn('order_items', 'refund_days')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->unsignedTinyInteger('refund_days')->nullable()->after('unit_price');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('order_items', 'refund_days')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->dropColumn('refund_days');
            });
        }

        if (Schema::hasColumn('inventories', 'refund_days')) {
            Schema::table('inventories', function (Blueprint $table) {
                $table->dropColumn('refund_days');
            });
        }
    }
};
