<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Api\Vendor\Concerns\ResolvesVendorShop;
use App\Http\Controllers\Controller;
use App\Models\Config;
use App\Models\Inventory;
use Illuminate\Http\Request;

class AffiliateController extends Controller
{
    use ResolvesVendorShop;

    public function index()
    {
        $config = Config::findOrFail($this->merchantShopId());

        return response()->json([
            'data' => [
                'enabled' => is_incevio_package_loaded('affiliate'),
                'default_commission_percentage' => $config->default_affiliate_commission_percentage,
                'products_with_commission' => Inventory::mine()
                    ->whereNotNull('affiliate_commission_percentage')
                    ->where('affiliate_commission_percentage', '>', 0)
                    ->count(),
            ],
        ]);
    }

    public function update(Request $request)
    {
        abort_unless(is_incevio_package_loaded('affiliate'), 404);

        $config = Config::findOrFail($this->merchantShopId());

        $request->validate([
            'default_affiliate_commission_percentage' => 'nullable|numeric|min:0|max:100',
        ]);

        $config->update($request->only(['default_affiliate_commission_percentage']));

        clearShopConfigCache($config->shop_id);

        return response()->json([
            'message' => trans('api.config_updated_successfully'),
            'data' => [
                'default_commission_percentage' => $config->default_affiliate_commission_percentage,
            ],
        ]);
    }
}
