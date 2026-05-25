<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('orders', 'subscription_transaction_fee')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->decimal('subscription_transaction_fee', 15, 2)
                    ->default(0)
                    ->after('platform_payment_fee');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('orders', 'subscription_transaction_fee')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('subscription_transaction_fee');
            });
        }
    }
};
