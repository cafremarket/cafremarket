<?php

use App\Models\PaymentMethod;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Production debug showed wallet_method=null because only mpesa/emola exist
 * in payment_methods — zcart-wallet row was never inserted (prior migrations
 * only UPDATE where code exists).
 */
return new class extends Migration
{
    private const CODE = 'zcart-wallet';

    public function up(): void
    {
        $now = now();

        if (! DB::table('payment_methods')->where('code', self::CODE)->exists()) {
            DB::table('payment_methods')->insert([
                'name' => 'Cafrepay',
                'code' => self::CODE,
                'type' => PaymentMethod::DIGITAL_WALLET,
                'split_money' => true,
                'company_name' => 'Cafrepay',
                'description' => 'Pay with your Cafrepay wallet balance.',
                'admin_description' => 'Cafrepay digital wallet for customers and merchants.',
                'admin_help_doc_link' => 'https://incevio.com/plugin/wallet',
                'enabled' => 1,
                'order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            DB::table('payment_methods')->where('code', self::CODE)->update([
                'name' => 'Cafrepay',
                'enabled' => 1,
                'updated_at' => $now,
            ]);
        }

        $methodId = DB::table('payment_methods')->where('code', self::CODE)->value('id');
        if (! $methodId) {
            return;
        }

        foreach (DB::table('shops')->pluck('id') as $shopId) {
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

        Cache::forget('package.wallet');
    }

    public function down(): void
    {
        //
    }
};
