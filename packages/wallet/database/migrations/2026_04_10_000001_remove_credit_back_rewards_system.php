<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remove the Credit Back Rewards feature from the database.
     */
    public function up(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            if (Schema::hasColumn('shops', 'total_reward_given')) {
                $table->dropColumn('total_reward_given');
            }
        });

        Schema::table('order_items', function (Blueprint $table) {
            if (Schema::hasColumn('order_items', 'credit_back_amount')) {
                $table->dropColumn('credit_back_amount');
            }
        });

        Schema::table('inventories', function (Blueprint $table) {
            if (Schema::hasColumn('inventories', 'credit_back_percentage')) {
                $table->dropColumn('credit_back_percentage');
            }
        });

        Schema::table('configs', function (Blueprint $table) {
            if (Schema::hasColumn('configs', 'credit_back_percentage')) {
                $table->dropColumn('credit_back_percentage');
            }
        });

        Schema::dropIfExists('wallet_credit_rewards');

        if (function_exists('get_option_table_name')) {
            DB::table(get_option_table_name())
                ->whereIn('option_name', [
                    'wallet_credit_reward_system',
                    'wallet_release_credit_rewards_in_days',
                ])
                ->delete();
        }

        Cache::forget('wallet_credit_reward_system');
        Cache::forget('wallet_release_credit_rewards_in_days');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Intentionally left empty — credit back rewards feature is removed.
    }
};
