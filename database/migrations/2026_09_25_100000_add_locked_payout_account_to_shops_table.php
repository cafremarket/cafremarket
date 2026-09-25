<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            // The single payout account (bank / M-Pesa / eMola) a vendor withdraws to.
            // Registered on the first withdrawal, then locked: only an admin can reset it.
            if (! Schema::hasColumn('shops', 'payout_method')) {
                $table->string('payout_method', 32)->nullable()->after('pay_to');
            }
            if (! Schema::hasColumn('shops', 'payout_details')) {
                $table->json('payout_details')->nullable()->after('payout_method');
            }
            if (! Schema::hasColumn('shops', 'payout_locked_at')) {
                $table->timestamp('payout_locked_at')->nullable()->after('payout_details');
            }
        });
    }

    public function down(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            foreach (['payout_locked_at', 'payout_details', 'payout_method'] as $column) {
                if (Schema::hasColumn('shops', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
