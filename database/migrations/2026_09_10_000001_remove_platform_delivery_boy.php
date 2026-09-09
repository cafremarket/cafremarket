<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_boys', function (Blueprint $table) {
            $table->dropColumn('type');
        });

        Schema::table('shops', function (Blueprint $table) {
            $table->dropColumn('delivery_capability');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['delivery_mode', 'delivery_assigned_at', 'delivery_dispatched_at']);
        });

        Schema::table('systems', function (Blueprint $table) {
            $table->dropColumn(['max_delivery_assignment_radius_km', 'rider_accept_timeout_min']);
        });
    }

    public function down(): void
    {
        Schema::table('systems', function (Blueprint $table) {
            $table->decimal('max_delivery_assignment_radius_km', 8, 2)->default(15)->after('default_buyer_search_radius_km');
            $table->unsignedSmallInteger('rider_accept_timeout_min')->default(5)->after('max_delivery_assignment_radius_km');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->enum('delivery_mode', ['shop', 'system'])->nullable()->after('delivery_boy_id');
            $table->timestamp('delivery_assigned_at')->nullable()->after('delivery_mode');
            $table->timestamp('delivery_dispatched_at')->nullable()->after('delivery_assigned_at');
        });

        Schema::table('shops', function (Blueprint $table) {
            $table->enum('delivery_capability', ['shop_only', 'system_only', 'both'])->default('both')->after('service_radius_km');
        });

        Schema::table('delivery_boys', function (Blueprint $table) {
            $table->enum('type', ['shop', 'platform'])->default('shop')->after('shop_id');
        });
    }
};
