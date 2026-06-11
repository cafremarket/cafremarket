<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;

class DiagnoseProducts extends Command
{
    protected $signature = 'products:diagnose {user_id? : Merchant or admin user id to simulate listing}';

    protected $description = 'Diagnose why products may not appear in admin or vendor app';

    public function handle(): int
    {
        $total = Product::count();
        $nullShop = Product::whereNull('shop_id')->count();
        $trashed = Product::onlyTrashed()->count();

        $this->info('Products diagnose');
        $this->line('Total products: '.$total);
        $this->line('With null shop_id: '.$nullShop);
        $this->line('Soft-deleted: '.$trashed);

        $this->newLine();
        $this->comment('Products per shop_id:');
        Product::query()
            ->selectRaw('shop_id, count(*) as total')
            ->groupBy('shop_id')
            ->orderByDesc('total')
            ->get()
            ->each(function ($row) {
                $shopName = $row->shop_id
                    ? optional(Shop::find($row->shop_id))->name
                    : '(none)';
                $this->line("  shop_id={$row->shop_id} ({$shopName}): {$row->total}");
            });

        $userId = $this->argument('user_id');

        if ($userId) {
            $user = User::find($userId);

            if (! $user) {
                $this->error("User {$userId} not found.");

                return self::FAILURE;
            }

            Auth::login($user);

            $ownedShop = $user->owns()->first();
            $merchantShop = $user->merchantShop();

            $this->newLine();
            $this->comment("User #{$user->id} ({$user->email})");
            $this->line('  role_id: '.$user->role_id);
            $this->line('  users.shop_id: '.($user->shop_id ?? 'null'));
            $this->line('  owned shop id: '.($ownedShop?->id ?? 'none'));
            $this->line('  merchantShop id: '.($merchantShop?->id ?? 'none'));
            $this->line('  merchantId(): '.($user->merchantId() ?? 'null'));
            $this->line('  isFromMerchant(): '.($user->isFromMerchant() ? 'yes' : 'no'));
            $this->line('  isFromPlatform(): '.($user->isFromPlatform() ? 'yes' : 'no'));
            $this->line('  Product::mine() count: '.Product::mine()->count());

            $wrongShop = Product::query()
                ->when($user->merchantId(), fn ($q) => $q->where('shop_id', '!=', $user->merchantId()))
                ->when(! $user->merchantId(), fn ($q) => $q->whereNotNull('shop_id'))
                ->count();

            if ($user->isFromMerchant() && $wrongShop > 0) {
                $this->warn("  {$wrongShop} product(s) exist with a different shop_id (invisible to this merchant).");
            }
        }

        $orphans = Product::whereNull('shop_id')->orWhereNotIn('shop_id', Shop::pluck('id'))->count();

        if ($orphans > 0) {
            $this->newLine();
            $this->warn("{$orphans} product(s) have missing or invalid shop_id. Run products:repair-shop-id to fix owned-shop mismatches.");
        }

        $this->newLine();
        $this->comment('Usage: php artisan products:diagnose {merchant_user_id}');

        return self::SUCCESS;
    }
}
