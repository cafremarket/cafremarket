<?php

use App\Models\PaymentMethod;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! DB::table('payment_methods')->where('code', 'emola')->exists()) {
            $now = Carbon::now();

            DB::table('payment_methods')->insert([
                'name' => 'eMola (Movitel)',
                'code' => 'emola',
                'type' => PaymentMethod::MOBILE_WALLET,
                'split_money' => false,
                'company_name' => 'Movitel / eMola',
                'website' => '',
                'help_doc_url' => '',
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
    }

    public function down(): void
    {
        DB::table('payment_methods')->where('code', 'emola')->delete();
    }
};

