<?php

namespace Incevio\Package\Wallet\Database\Seeds;

use App\Helpers\PackageSeeder;
use App\Models\PaymentMethod;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class WalletPaymentMethodSeeder extends PackageSeeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (! DB::table('payment_methods')->where('code', 'zcart-wallet')->exists()) {
            DB::table('payment_methods')->insert([
                'name' => 'Cafrepay',
                'code' => 'zcart-wallet',
                'type' => PaymentMethod::DIGITAL_WALLET,
                'split_money' => true,
                'enabled' => true,
                'company_name' => 'Cafrepay',
                'description' => 'Pay with your Cafrepay wallet balance.',
                'admin_description' => 'Cafrepay is the marketplace wallet. Customers pay from wallet balance; merchants receive sales after subscription commission.',
                'admin_help_doc_link' => 'https://incevio.com/plugin/wallet',
                'order' => 1,
                'created_at' => Carbon::Now(),
                'updated_at' => Carbon::Now(),
            ]);
        } else {
            DB::table('payment_methods')->where('code', 'zcart-wallet')->update([
                'name' => 'Cafrepay',
                'enabled' => true,
                'company_name' => 'Cafrepay',
                'updated_at' => Carbon::Now(),
            ]);
        }

        // Seed Permissions
        $actions = 'setting,payout,report';
        $this->seedPermissions('Wallet', 'Platform', $actions);
    }
}
