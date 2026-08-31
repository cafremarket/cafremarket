<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->decimal('service_radius_km', 8, 2)->default(5)->after('address_verified');
            $table->enum('delivery_capability', ['shop_only', 'system_only', 'both'])->default('both')->after('service_radius_km');
            $table->unsignedBigInteger('primary_address_id')->nullable()->after('delivery_capability');
        });

        Schema::table('delivery_boys', function (Blueprint $table) {
            $table->enum('type', ['shop', 'platform'])->default('shop')->after('shop_id');
            $table->boolean('is_online')->default(false)->after('status');
            $table->decimal('current_latitude', 10, 7)->nullable()->after('is_online');
            $table->decimal('current_longitude', 10, 7)->nullable()->after('current_latitude');
            $table->timestamp('last_location_at')->nullable()->after('current_longitude');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->enum('delivery_mode', ['shop', 'system'])->nullable()->after('delivery_boy_id');
            $table->timestamp('delivery_assigned_at')->nullable()->after('delivery_mode');
            $table->timestamp('delivery_dispatched_at')->nullable()->after('delivery_assigned_at');
            $table->decimal('customer_latitude', 10, 7)->nullable()->after('delivery_dispatched_at');
            $table->decimal('customer_longitude', 10, 7)->nullable()->after('customer_latitude');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->decimal('preferred_latitude', 10, 7)->nullable()->after('fcm_token');
            $table->decimal('preferred_longitude', 10, 7)->nullable()->after('preferred_latitude');
            $table->string('preferred_address_text')->nullable()->after('preferred_longitude');
        });

        Schema::table('systems', function (Blueprint $table) {
            $table->decimal('default_buyer_search_radius_km', 8, 2)->default(10)->after('vendor_needs_approval');
            $table->decimal('max_delivery_assignment_radius_km', 8, 2)->default(15)->after('default_buyer_search_radius_km');
            $table->unsignedSmallInteger('rider_accept_timeout_min')->default(5)->after('max_delivery_assignment_radius_km');
        });

        Schema::table('addresses', function (Blueprint $table) {
            $table->index(['latitude', 'longitude'], 'addresses_lat_lng_index');
        });
    }

    public function down(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->dropIndex('addresses_lat_lng_index');
        });

        Schema::table('systems', function (Blueprint $table) {
            $table->dropColumn(['default_buyer_search_radius_km', 'max_delivery_assignment_radius_km', 'rider_accept_timeout_min']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['preferred_latitude', 'preferred_longitude', 'preferred_address_text']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['delivery_mode', 'delivery_assigned_at', 'delivery_dispatched_at', 'customer_latitude', 'customer_longitude']);
        });

        Schema::table('delivery_boys', function (Blueprint $table) {
            $table->dropColumn(['type', 'is_online', 'current_latitude', 'current_longitude', 'last_location_at']);
        });

        Schema::table('shops', function (Blueprint $table) {
            $table->dropColumn(['service_radius_km', 'delivery_capability', 'primary_address_id']);
        });
    }
};
