<?php

use App\Models\PaymentMethod;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = Carbon::now();

        if (! DB::table('payment_methods')->where('code', 'emola')->exists()) {
            DB::table('payment_methods')->insert([
                'name' => 'eMola (Movitel)',
                'code' => 'emola',
                'type' => PaymentMethod::MOBILE_WALLET,
                'split_money' => false,
                'company_name' => 'Movitel / eMola',
                'website' => '',
                'help_doc_link' => '',
                'admin_help_doc_link' => '',
                'terms_conditions_link' => '',
                'description' => 'Pay using eMola (Movitel) via USSD Push.',
                'instructions' => null,
                'admin_description' => 'eMola USSD Push payment integration (SOAP gateway + optional async callback).',
                'enabled' => 1,
                'order' => 4,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $emolaId = DB::table('payment_methods')->where('code', 'emola')->value('id');

        if (! $emolaId) {
            return;
        }

        $shopIds = DB::table('shops')->pluck('id');

        foreach ($shopIds as $shopId) {
            $exists = DB::table('shop_payment_methods')
                ->where('shop_id', $shopId)
                ->where('payment_method_id', $emolaId)
                ->exists();

            if (! $exists) {
                DB::table('shop_payment_methods')->insert([
                    'shop_id' => $shopId,
                    'payment_method_id' => $emolaId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        $emolaId = DB::table('payment_methods')->where('code', 'emola')->value('id');

        if ($emolaId) {
            DB::table('shop_payment_methods')->where('payment_method_id', $emolaId)->delete();
        }
    }
};
