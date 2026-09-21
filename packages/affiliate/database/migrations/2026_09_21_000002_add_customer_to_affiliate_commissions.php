<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('affiliate_commissions')) {
            return;
        }

        Schema::table('affiliate_commissions', function (Blueprint $table) {
            if (! Schema::hasColumn('affiliate_commissions', 'customer_id')) {
                $table->unsignedBigInteger('customer_id')->nullable()->after('order_id');
                $table->index('customer_id');
            }

            if (! Schema::hasColumn('affiliate_commissions', 'customer_email')) {
                $table->string('customer_email')->nullable()->after('customer_id');
                $table->index('customer_email');
            }

            if (! Schema::hasColumn('affiliate_commissions', 'clicked_at')) {
                $table->timestamp('clicked_at')->nullable()->after('customer_email');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('affiliate_commissions')) {
            return;
        }

        Schema::table('affiliate_commissions', function (Blueprint $table) {
            if (Schema::hasColumn('affiliate_commissions', 'clicked_at')) {
                $table->dropColumn('clicked_at');
            }

            if (Schema::hasColumn('affiliate_commissions', 'customer_email')) {
                $table->dropIndex(['customer_email']);
                $table->dropColumn('customer_email');
            }

            if (Schema::hasColumn('affiliate_commissions', 'customer_id')) {
                $table->dropIndex(['customer_id']);
                $table->dropColumn('customer_id');
            }
        });
    }
};
