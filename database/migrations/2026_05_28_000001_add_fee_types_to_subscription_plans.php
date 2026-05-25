<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('subscription_plans', 'transaction_fee_type')) {
            Schema::table('subscription_plans', function (Blueprint $table) {
                $table->string('transaction_fee_type', 10)->default('flat')->after('transaction_fee');
                $table->string('marketplace_commission_type', 10)->default('percent')->after('marketplace_commission');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('subscription_plans', 'transaction_fee_type')) {
            Schema::table('subscription_plans', function (Blueprint $table) {
                $table->dropColumn(['transaction_fee_type', 'marketplace_commission_type']);
            });
        }
    }
};
