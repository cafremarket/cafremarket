<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('configs') || ! Schema::hasColumn('configs', 'default_affiliate_commission_percentage')) {
            return;
        }

        DB::table('configs')
            ->whereNull('default_affiliate_commission_percentage')
            ->update(['default_affiliate_commission_percentage' => 5]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Intentionally left blank — previous null defaults are not restored.
    }
};
