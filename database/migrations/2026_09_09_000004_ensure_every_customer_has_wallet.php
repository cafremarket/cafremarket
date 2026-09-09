<?php

use App\Models\Customer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Backfill a default Cafrepay wallet for every customer that does not have one.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('wallets') || ! Schema::hasTable('customers')) {
            return;
        }

        $holderType = Customer::class;
        $slug = 'default';
        $name = 'Cafre-pay';
        $now = now();

        $customerIds = DB::table('customers')
            ->whereNull('deleted_at')
            ->pluck('id');

        foreach ($customerIds as $customerId) {
            $exists = DB::table('wallets')
                ->where('holder_type', $holderType)
                ->where('holder_id', $customerId)
                ->where('slug', $slug)
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('wallets')->insert([
                'holder_type' => $holderType,
                'holder_id' => $customerId,
                'name' => $name,
                'slug' => $slug,
                'description' => null,
                'meta' => null,
                'balance' => 0,
                'blocked' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        // Keep wallets — do not wipe customer balances on rollback.
    }
};
