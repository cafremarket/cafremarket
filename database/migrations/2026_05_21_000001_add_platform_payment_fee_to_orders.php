<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('orders', 'platform_payment_fee')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->decimal('platform_payment_fee', 15, 2)->default(0)->after('grand_total');
            });
        }

        $table = DB::getTablePrefix().'options';
        $now = now();
        $defaults = [
            ['platform_fee_mpesa_enabled', 0],
            ['platform_fee_mpesa_type', 'flat'],
            ['platform_fee_mpesa_value', 0],
            ['platform_fee_emola_enabled', 0],
            ['platform_fee_emola_type', 'flat'],
            ['platform_fee_emola_value', 0],
            ['platform_fee_payout_enabled', 0],
            ['platform_fee_payout_type', 'flat'],
            ['platform_fee_payout_value', 0],
        ];

        foreach ($defaults as [$name, $value]) {
            if (! DB::table('options')->where('option_name', $name)->exists()) {
                DB::table('options')->insert([
                    'option_name' => $name,
                    'option_value' => $value,
                    'autoload' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('orders', 'platform_payment_fee')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('platform_payment_fee');
            });
        }

        DB::table('options')->whereIn('option_name', [
            'platform_fee_mpesa_enabled',
            'platform_fee_mpesa_type',
            'platform_fee_mpesa_value',
            'platform_fee_emola_enabled',
            'platform_fee_emola_type',
            'platform_fee_emola_value',
            'platform_fee_payout_enabled',
            'platform_fee_payout_type',
            'platform_fee_payout_value',
        ])->delete();
    }
};
