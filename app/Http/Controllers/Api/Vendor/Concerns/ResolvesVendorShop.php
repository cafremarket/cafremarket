<?php

namespace App\Http\Controllers\Api\Vendor\Concerns;

use App\Models\Shop;
use Illuminate\Support\Facades\Auth;

trait ResolvesVendorShop
{
    protected function merchantShopId(): int
    {
        $user = Auth::guard('vendor_api')->user() ?? Auth::user();

        return $user ? (int) $user->merchantId() : 0;
    }

    protected function assertOwnsShop(int $shopId): void
    {
        $merchantShopId = $this->merchantShopId();

        abort_unless(
            $merchantShopId > 0 && $merchantShopId === $shopId,
            403,
            trans('responses.unauthorized')
        );
    }

    protected function shop(): Shop
    {
        $shopId = $this->merchantShopId();

        abort_unless($shopId > 0, 403, trans('packages.wallet.owner_invalid'));

        $shop = Shop::find($shopId);

        abort_unless($shop, 403, trans('packages.wallet.owner_invalid'));

        return $shop;
    }
}
