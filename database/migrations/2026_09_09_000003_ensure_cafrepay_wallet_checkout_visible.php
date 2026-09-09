<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Ensure Cafrepay (zcart-wallet) can pass paymentOptions filters on production
 * even when CUSTOMER_HAS_WALLET was never set in .env / config cache.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('payment_methods')->where('code', 'zcart-wallet')->update([
            'enabled' => 1,
            'name' => 'Cafrepay',
            'updated_at' => now(),
        ]);

        DB::table('packages')->where('slug', 'wallet')->update([
            'active' => 1,
            'updated_at' => now(),
        ]);

        $table = function_exists('get_option_table_name')
            ? get_option_table_name()
            : 'options';

        if (DB::getSchemaBuilder()->hasTable($table)) {
            $existing = DB::table($table)->where('option_name', 'wallet_checkout')->first();
            if ($existing) {
                DB::table($table)->where('option_name', 'wallet_checkout')->update([
                    'option_value' => '1',
                    'updated_at' => now(),
                ]);
            } else {
                DB::table($table)->insert([
                    'option_name' => 'wallet_checkout',
                    'option_value' => '1',
                    'autoload' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        Cache::forget('package.wallet');
        Cache::forget('system_settings');
    }

    public function down(): void
    {
        //
    }
};
