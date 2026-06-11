<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Console\Command;

class RepairProductShopIds extends Command
{
    protected $signature = 'products:repair-shop-id {--dry-run : Show changes without saving}';

    protected $description = 'Assign products to the correct shop based on shop owner user_id on inventories or shop ownership';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $fixed = 0;

        $products = Product::with('inventories')->get();

        foreach ($products as $product) {
            $targetShopId = $this->resolveShopId($product);

            if (! $targetShopId || (int) $product->shop_id === (int) $targetShopId) {
                continue;
            }

            $this->line("Product #{$product->id} \"{$product->name}\": shop_id {$product->shop_id} -> {$targetShopId}");

            if (! $dryRun) {
                $product->forceFill(['shop_id' => $targetShopId])->saveQuietly();
            }

            $fixed++;
        }

        // Sync users.shop_id from owned shop
        $usersFixed = 0;
        User::where('role_id', 3)->chunkById(100, function ($users) use ($dryRun, &$usersFixed) {
            foreach ($users as $user) {
                $owned = Shop::where('owner_id', $user->id)->first();

                if (! $owned || (int) $user->shop_id === (int) $owned->id) {
                    continue;
                }

                $this->line("User #{$user->id}: shop_id {$user->shop_id} -> {$owned->id}");

                if (! $dryRun) {
                    $user->forceFill(['shop_id' => $owned->id])->saveQuietly();
                }

                $usersFixed++;
            }
        });

        $this->newLine();
        $this->info(($dryRun ? '[dry-run] ' : '')."Updated {$fixed} product(s), {$usersFixed} merchant user(s).");

        return self::SUCCESS;
    }

    private function resolveShopId(Product $product): ?int
    {
        $inventoryShopId = $product->inventories->first()?->shop_id;

        if ($inventoryShopId && Shop::whereKey($inventoryShopId)->exists()) {
            return (int) $inventoryShopId;
        }

        if ($product->shop_id && Shop::whereKey($product->shop_id)->exists()) {
            return (int) $product->shop_id;
        }

        return null;
    }
}
