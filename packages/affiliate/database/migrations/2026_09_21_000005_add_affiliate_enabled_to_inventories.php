<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('inventories')) {
            return;
        }

        Schema::table('inventories', function (Blueprint $table) {
            if (! Schema::hasColumn('inventories', 'affiliate_enabled')) {
                $table->boolean('affiliate_enabled')->default(true)->after('affiliate_commission_percentage');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('inventories') || ! Schema::hasColumn('inventories', 'affiliate_enabled')) {
            return;
        }

        Schema::table('inventories', function (Blueprint $table) {
            $table->dropColumn('affiliate_enabled');
        });
    }
};
