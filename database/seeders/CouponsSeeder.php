<?php

namespace Database\Seeders;

use App\Helpers\PackageSeeder;

class CouponsSeeder extends PackageSeeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Seed Permissions
        $actions = 'view,add,edit,delete';
        $this->seedPermissions('Coupon', 'Merchant', $actions);
    }
}
