<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            Schema::hasTable('subscriptions')
            && Schema::hasColumn('subscriptions', 'stripe_price')
            && ! Schema::hasColumn('subscriptions', 'billing_plan')
        ) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->renameColumn('stripe_price', 'billing_plan');
            });
        }
    }

    public function down(): void
    {
        if (
            Schema::hasTable('subscriptions')
            && Schema::hasColumn('subscriptions', 'billing_plan')
            && ! Schema::hasColumn('subscriptions', 'stripe_price')
        ) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->renameColumn('billing_plan', 'stripe_price');
            });
        }
    }
};
