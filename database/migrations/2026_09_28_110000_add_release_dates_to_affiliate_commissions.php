<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Affiliate commissions are credited only after the item's refund/return period:
 * release_at = when it becomes payable, voided_at = cancelled because the order was refunded.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('affiliate_commissions')) {
            return;
        }

        Schema::table('affiliate_commissions', function (Blueprint $table) {
            if (! Schema::hasColumn('affiliate_commissions', 'release_at')) {
                $table->timestamp('release_at')->nullable()->after('paid');
            }
            if (! Schema::hasColumn('affiliate_commissions', 'voided_at')) {
                $table->timestamp('voided_at')->nullable()->after('release_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('affiliate_commissions')) {
            return;
        }

        Schema::table('affiliate_commissions', function (Blueprint $table) {
            foreach (['voided_at', 'release_at'] as $column) {
                if (Schema::hasColumn('affiliate_commissions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
