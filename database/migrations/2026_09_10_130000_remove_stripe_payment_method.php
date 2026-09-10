<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remove Stripe completely from buyer/admin payment methods.
     * Subscription Cashier config is left untouched.
     */
    public function up(): void
    {
        $stripeId = DB::table('payment_methods')->where('code', 'stripe')->value('id');

        if ($stripeId) {
            if (Schema::hasTable('shop_payment_methods')) {
                DB::table('shop_payment_methods')
                    ->where('payment_method_id', $stripeId)
                    ->delete();
            }

            if (Schema::hasTable('config_manual_payment_methods')) {
                DB::table('config_manual_payment_methods')
                    ->where('payment_method_id', $stripeId)
                    ->delete();
            }

            // Some installs pivot payment methods on configs via shop_payment_methods only;
            // also clear any generic pivot if present.
            if (Schema::hasTable('config_payment_methods')) {
                DB::table('config_payment_methods')
                    ->where('payment_method_id', $stripeId)
                    ->delete();
            }

            $table = function_exists('get_option_table_name')
                ? get_option_table_name()
                : (config('system_settings.option_table_name') ?: 'options');

            if (Schema::hasTable($table)) {
                $row = DB::table($table)->where('option_name', 'wallet_payment_methods')->first();
                if ($row) {
                    $methods = @unserialize($row->option_value);
                    if (is_array($methods)) {
                        $methods = array_values(array_filter(
                            $methods,
                            fn ($id) => (string) $id !== (string) $stripeId
                        ));
                        DB::table($table)->where('option_name', 'wallet_payment_methods')->update([
                            'option_value' => serialize($methods),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }

            DB::table('payment_methods')->where('id', $stripeId)->delete();
        }

        Cache::forget('wallet_payment_methods');
        Cache::forget('system_settings');
    }

    public function down(): void
    {
        // Stripe payment method is not restored automatically.
    }
};
