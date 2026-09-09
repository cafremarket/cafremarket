<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->enum('fulfillment_method', ['delivery_boy', 'courier'])->nullable()->after('delivery_boy_id');
            $table->timestamp('reached_at')->nullable()->after('fulfillment_method');
            $table->string('courier_name')->nullable()->after('reached_at');
            $table->string('courier_phone')->nullable()->after('courier_name');
            $table->string('courier_tracking_number')->nullable()->after('courier_phone');
            $table->timestamp('courier_added_at')->nullable()->after('courier_tracking_number');
            $table->timestamp('delivered_confirmed_at')->nullable()->after('courier_added_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'fulfillment_method',
                'reached_at',
                'courier_name',
                'courier_phone',
                'courier_tracking_number',
                'courier_added_at',
                'delivered_confirmed_at',
            ]);
        });
    }
};
