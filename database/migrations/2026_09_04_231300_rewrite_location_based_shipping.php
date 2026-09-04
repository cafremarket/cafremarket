<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configs', function (Blueprint $table) {
            if (! Schema::hasColumn('configs', 'shipping_type')) {
                $table->string('shipping_type', 20)->default('fixed')->after('order_handling_cost');
            }
            if (! Schema::hasColumn('configs', 'shipping_fixed_rate')) {
                $table->decimal('shipping_fixed_rate', 20, 6)->nullable()->after('shipping_type');
            }
            if (! Schema::hasColumn('configs', 'shipping_per_km_rate')) {
                $table->decimal('shipping_per_km_rate', 20, 6)->nullable()->after('shipping_fixed_rate');
            }
            if (! Schema::hasColumn('configs', 'shipping_base_fee')) {
                $table->decimal('shipping_base_fee', 20, 6)->default(0)->after('shipping_per_km_rate');
            }
        });

        Schema::table('inventories', function (Blueprint $table) {
            if (! Schema::hasColumn('inventories', 'shipping_type')) {
                $table->string('shipping_type', 20)->nullable()->after('free_shipping');
            }
            if (! Schema::hasColumn('inventories', 'shipping_fixed_rate')) {
                $table->decimal('shipping_fixed_rate', 20, 6)->nullable()->after('shipping_type');
            }
            if (! Schema::hasColumn('inventories', 'shipping_per_km_rate')) {
                $table->decimal('shipping_per_km_rate', 20, 6)->nullable()->after('shipping_fixed_rate');
            }
            if (! Schema::hasColumn('inventories', 'shipping_base_fee')) {
                $table->decimal('shipping_base_fee', 20, 6)->nullable()->after('shipping_per_km_rate');
            }
        });

        // Migrate legacy free_shipping inventory flags into per-item free mode.
        if (Schema::hasColumn('inventories', 'shipping_type')) {
            \DB::table('inventories')
                ->where('free_shipping', 1)
                ->whereNull('shipping_type')
                ->update(['shipping_type' => 'free']);
        }
    }

    public function down(): void
    {
        Schema::table('configs', function (Blueprint $table) {
            foreach (['shipping_base_fee', 'shipping_per_km_rate', 'shipping_fixed_rate', 'shipping_type'] as $col) {
                if (Schema::hasColumn('configs', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('inventories', function (Blueprint $table) {
            foreach (['shipping_base_fee', 'shipping_per_km_rate', 'shipping_fixed_rate', 'shipping_type'] as $col) {
                if (Schema::hasColumn('inventories', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
