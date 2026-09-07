<?php

use App\Models\PaymentMethod;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // M-Pesa ships as an Incevio package (packages/mpesa) with its own
    // payment-method seeder (MPesaSeeder), but neither the `packages` row
    // nor the `payment_methods` row was ever inserted, so M-Pesa could
    // never appear at checkout even though its API credentials are already
    // configured in .env (MPESA_MZ_* — confirmed via
    // SystemConfig::isPaymentConfigured('mpesa')).
    public function up(): void
    {
        $now = now();

        if (! DB::table('payment_methods')->where('code', 'mpesa')->exists()) {
            DB::table('payment_methods')->insert([
                'name' => 'M-Pesa',
                'code' => 'mpesa',
                'type' => PaymentMethod::MOBILE_WALLET,
                'split_money' => false,
                'company_name' => 'M-Pesa',
                'website' => 'https://www.vm.co.mz/m-pesa',
                'help_doc_link' => 'https://www.vm.co.mz/m-pesa',
                'description' => 'Pay using M-Pesa (Vodacom Mozambique) via STK push.',
                'admin_description' => 'M-Pesa Mozambique (Vodacom Open API) payment integration.',
                'admin_help_doc_link' => 'https://developer.mpesa.vm.co.mz/Documentation',
                'enabled' => true,
                'order' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            DB::table('payment_methods')->where('code', 'mpesa')->update([
                'enabled' => true,
                'updated_at' => $now,
            ]);
        }

        if (! DB::table('packages')->where('slug', 'mpesa')->exists()) {
            DB::table('packages')->insert([
                'slug' => 'mpesa',
                'name' => 'M-Pesa Mozambique',
                'description' => 'M-Pesa Mozambique (Vodacom Open API) payment for your marketplace',
                'compatible' => '2.8.0',
                'dependency' => '',
                'version' => '1.2.0',
                'active' => true,
                'icon' => 'money',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            DB::table('packages')->where('slug', 'mpesa')->update([
                'active' => true,
                'updated_at' => $now,
            ]);
        }

        // is_incevio_package_loaded() caches the "is mpesa active" lookup
        // forever — forget it so the change takes effect immediately.
        Cache::forget('package.mpesa');
    }

    public function down(): void
    {
        DB::table('payment_methods')->where('code', 'mpesa')->update(['enabled' => false]);
        DB::table('packages')->where('slug', 'mpesa')->update(['active' => false]);
        Cache::forget('package.mpesa');
    }
};
