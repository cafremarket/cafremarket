<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Ensure Cafrepay (zcart-wallet) can appear at checkout on production:
     * - wallet package row active
     * - wallet_checkout option on
     * - mpesa / emola / zcart-wallet enabled and linked to every shop
     */
    private const CHECKOUT_CODES = [
        'mpesa',
        'emola',
        'zcart-wallet',
    ];

    public function up(): void
    {
        $now = now();

        // 1) Activate Cafrepay wallet package (is_incevio_package_loaded checks this + class).
        if (! DB::table('packages')->where('slug', 'wallet')->exists()) {
            DB::table('packages')->insert([
                'slug' => 'wallet',
                'name' => 'Cafrepay',
                'description' => 'Cafrepay digital wallet for customers and merchants.',
                'compatible' => '2.20.0',
                'dependency' => '',
                'version' => '1.7.0',
                'active' => true,
                'icon' => 'money',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            DB::table('packages')->where('slug', 'wallet')->update([
                'active' => true,
                'name' => 'Cafrepay',
                'updated_at' => $now,
            ]);
        }

        // 2) Enable buyer-facing gateways.
        DB::table('payment_methods')
            ->whereIn('code', self::CHECKOUT_CODES)
            ->update(['enabled' => 1, 'updated_at' => $now]);

        DB::table('payment_methods')
            ->where('code', 'zcart-wallet')
            ->update(['name' => 'Cafrepay', 'updated_at' => $now]);

        // 3) Allow wallet at checkout (options table).
        $table = function_exists('get_option_table_name')
            ? get_option_table_name()
            : 'options';

        if (DB::getSchemaBuilder()->hasTable($table)) {
            $existing = DB::table($table)->where('option_name', 'wallet_checkout')->first();
            if ($existing) {
                DB::table($table)->where('option_name', 'wallet_checkout')->update([
                    'option_value' => '1',
                    'autoload' => 0,
                    'updated_at' => $now,
                ]);
            } else {
                DB::table($table)->insert([
                    'option_name' => 'wallet_checkout',
                    'option_value' => '1',
                    'autoload' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        // 4) Attach enabled gateways to every shop (shop paymentOptions + vendor_get_paid_directly).
        $methodIds = DB::table('payment_methods')
            ->whereIn('code', self::CHECKOUT_CODES)
            ->pluck('id');

        $shopIds = DB::table('shops')->pluck('id');

        foreach ($shopIds as $shopId) {
            foreach ($methodIds as $methodId) {
                $exists = DB::table('shop_payment_methods')
                    ->where('shop_id', $shopId)
                    ->where('payment_method_id', $methodId)
                    ->exists();

                if (! $exists) {
                    DB::table('shop_payment_methods')->insert([
                        'shop_id' => $shopId,
                        'payment_method_id' => $methodId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }

        // Forever caches used by is_incevio_package_loaded / settings.
        Cache::forget('package.wallet');
        Cache::forget('system_settings');
    }

    public function down(): void
    {
        // Do not deactivate wallet or detach shop methods automatically.
        Cache::forget('package.wallet');
    }
};
