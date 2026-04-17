<?php

namespace Icecevio\Package\Affiliate\Database\Seeds;

use App\Models\PaymentMethod;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AffiliateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('affiliates')->insert([
            'id' => 1,
            'name' => 'Demo Affiliate',
            'username' => 'affiliate',
            'email' => 'affiliate@demo.com',
            'password' => bcrypt('123456'),
            'active' => 1,
            'created_at' => Carbon::Now(),
            'updated_at' => Carbon::Now(),
        ]);
    }
}
