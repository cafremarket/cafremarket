<?php

namespace App\Http\Controllers\Api;

use App\Common\InventorySearch;
use App\Http\Controllers\Controller;
use App\Http\Requests\Validations\ShippingOptionRequest;
use App\Http\Resources\BannerResource;
use App\Http\Resources\CountryResource;
use App\Http\Resources\CurrencyResource;
use App\Http\Resources\ManufacturerLightResource;
use App\Http\Resources\ManufacturerResource;
use App\Http\Resources\PageResource;
use App\Http\Resources\PaymentMethodResource;
use App\Http\Resources\ShippingOptionResource;
use App\Http\Resources\ShopLightResource;
use App\Http\Resources\ShopResource;
use App\Http\Resources\SliderResource;
use App\Http\Resources\StateResource;
use App\Http\Resources\SystemConfigResource;
use App\Http\Resources\WarehouseResource;
use App\Models\Banner;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Manufacturer;
use App\Models\Page;
use App\Models\PaymentMethod;
use App\Models\Shop;
use App\Models\Slider;
use App\Models\State;
use App\Http\Controllers\Api\Concerns\CachesApiResponses;
use App\Services\Cache\CatalogCache;
use App\Services\Shop\NearbyShopService;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    use CachesApiResponses;

    use InventorySearch;

    protected function setUp(): void
    {
        parent::setUp();

        Request::shouldReceive('ip')->andReturn('127.0.0.1');
    }

    /**
     * Get system's default configs.
     *
     * @return SystemConfigResource resource containing systems configs
     */
    public function system_configs()
    {
        $config = (object) config('system_settings');

        return new SystemConfigResource($config);
    }

    /**
     * Get listing of the resource.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection A collection of SliderResources
     */
    public function sliders(Request $request)
    {
        $shop_id = $request->get('shop_id');

        return $this->rememberApi('sliders:'.($shop_id ?: 'platform'), function () use ($shop_id) {
            $sliders = Slider::whereHas('mobileImage')
                ->with('mobileImage')
                ->where('shop_id', $shop_id)
                ->orderBy('order', 'asc')
                ->get();

            return SliderResource::collection($sliders);
        });
    }

    /**
     * Get listing of the Banner resource.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection A collection of BannerResources
     */
    public function banners(Request $request)
    {
        $shop_id = $request->get('shop_id');

        return $this->rememberApi('banners:'.($shop_id ?: 'platform'), function () use ($shop_id) {
            $banners = Banner::with(['featureImage'])
                ->where('shop_id', $shop_id)
                ->when($shop_id === null, fn ($q) => $q->forApp())
                ->orderBy('order', 'asc')
                ->get();

            return BannerResource::collection($banners);
        });
    }

    /**
     * Display Basic Details of all shops.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection The collection of ShopLightResource
     */
    public function allShops(Request $request, NearbyShopService $nearbyShopService)
    {
        if ($request->filled('lat') && $request->filled('lng')) {
            $lat = (float) $request->lat;
            $lng = (float) $request->lng;

            return $this->rememberApi('shops:nearby:'.CatalogCache::geoKey($lat, $lng), function () use ($nearbyShopService, $request, $lat, $lng) {
                $results = $nearbyShopService->find($lat, $lng);

                return [
                    'data' => $results->map(function ($row) use ($request) {
                        $address = $row['shop']->storeAddress();

                        return array_merge(
                            (new ShopLightResource($row['shop']))->toArray($request),
                            [
                                'distance_km' => $row['distance_km'],
                                'deliverable' => $row['deliverable'],
                                'latitude' => $address?->latitude ? (float) $address->latitude : null,
                                'longitude' => $address?->longitude ? (float) $address->longitude : null,
                            ]
                        );
                    })->values(),
                ];
            }, null, 'geo');
        }

        return $this->rememberApi('shops:all', function () {
            $shops = Shop::with([
                'logoImage:path,imageable_id,imageable_type',
                'avgFeedback:rating,count,feedbackable_id,feedbackable_type',
            ])
                ->withCount([
                    'inventories' => function ($q) {
                        $q->where('active', 1)
                            ->whereNull('parent_id');
                    },
                ])
                ->approved()
                ->whereHas('inventories')
                ->orderBy('name')
                ->get();

            return ShopLightResource::collection($shops);
        }, null, 'shops');
    }

    /**
     * Display the specified resource.
     *
     * @param  string  $slug  The slug of the shop.
     * @return ShopResource Contains shop details
     */
    public function shop($slug)
    {
        return $this->rememberApi('shop:'.$slug, function () use ($slug) {
            $shop = Shop::where('slug', $slug)->approved()
                ->with([
                    'latestFeedbacks' => function ($q) {
                        $q->with('customer:id,nice_name,name')->take(3);
                    },
                ])
                ->withCount([
                    'inventories' => function ($q) {
                        $q->where('active', 1)
                            ->whereNull('parent_id');
                    },
                ])
                ->firstOrFail();

            if ($shop->isDown()) {
                return response()->json(['message' => trans('app.marketplace_down')], 404);
            }

            return new ShopResource($shop);
        }, null, 'shops');
    }

    /**
     * Display warehouse resource for specified shop by slug.
     *
     * @param  string  $slug  The slug of the shop.
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection The collection of warehouse resources
     */
    public function showAllWarehousesOfShop(string $slug)
    {
        $shop = Shop::where('slug', $slug)->active()->firstOrFail();

        if (! $shop->config->isPickupEnabled()) {
            return response()->json(['message' => trans('app.pickup_not_available')], 404);
        }

        $warehouses = $shop->warehouses()->active()->get();

        return WarehouseResource::collection($warehouses);
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function allBrands()
    {
        return $this->rememberApi('brands:all', function () {
            $brands = Manufacturer::select('id', 'name', 'slug', 'description', 'country_id')
                ->with('logoImage:path,imageable_id,imageable_type')
                ->active()->get();

            return ManufacturerLightResource::collection($brands);
        });
    }

    /**
     * Featured Brands
     *
     * @return void
     */
    public function featuredBrands()
    {
        return ManufacturerLightResource::collection(collect([]));
    }

    /**
     * Display the details of the brand with the given slug.
     *
     * @param  string  $slug  The slug of the brand.
     * @return ManufacturerResource Contains brand/Manufacturer details
     */
    public function brand($slug)
    {
        return $this->rememberApi('brand:'.$slug, function () use ($slug) {
            $brand = Manufacturer::where('slug', $slug)->firstOrFail();

            return new ManufacturerResource($brand);
        });
    }

    /**
     * Return available shipping options for the specified shop.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $shop
     * @return \Illuminate\Http\Response
     */
    public function shipping(ShippingOptionRequest $request, Shop $shop)
    {
        $calculator = app(\App\Services\Shipping\ShippingCalculator::class);
        $destLat = $request->input('latitude') ?? $request->input('lat');
        $destLng = $request->input('longitude') ?? $request->input('lng');
        $lat = is_numeric($destLat) ? (float) $destLat : null;
        $lng = is_numeric($destLng) ? (float) $destLng : null;

        if ($request->cart) {
            $cart = \App\Models\Cart::find($request->cart);
            if ($cart && (int) $cart->shop_id === (int) $shop->id) {
                return ShippingOptionResource::collection(
                    $calculator->shippingOptionsPayload($cart, $lat, $lng)
                );
            }
        }

        $shop->loadMissing('config');
        $distanceKm = $calculator->distanceFromShop($shop, $lat, $lng);
        $config = $shop->config;
        $type = strtolower((string) ($config->shipping_type ?? 'fixed'));
        $amount = match ($type) {
            'free' => 0.0,
            default => max(0.0, (float) ($config->shipping_fixed_rate ?? $config->shipping_base_fee ?? 0)),
        };

        return ShippingOptionResource::collection(collect([(object) [
            'id' => 'location',
            'name' => $amount <= 0
                ? (trans('theme.free_shipping') ?: 'Free shipping')
                : (trans('app.shipping') ?: 'Shipping'),
            'shipping_zone_id' => null,
            'carrier_id' => null,
            'carrier_name' => trans('app.shipping') ?? 'Shipping',
            'rate' => $amount,
            'delivery_takes' => null,
            'distance_km' => $distanceKm,
        ]]));
    }

    /**
     * Return available payment options options for the specified shop.
     *
     * @param  string  $shop
     * @return \Illuminate\Http\Response
     */
    public function paymentOptions($shop)
    {
        // Get the shop
        $shop = Shop::where('slug', $shop)->active()->firstOrFail();

        // Get all active payment methods
        $activePaymentMethods = PaymentMethod::active()->get();
        $activePaymentCodes = $activePaymentMethods->pluck('code')->toArray();

        // Match CheckoutController: only force shop-scoped methods when vendors
        // are paid directly. Otherwise platform-configured gateways (M-Pesa,
        // eMola, Cafrepay) must still appear even if shop_payment_methods is empty.
        $shopConfig = null;
        if (vendor_get_paid_directly()) {
            $activePaymentMethods = $shop->paymentMethods;
            $shopConfig = $shop;
        }

        $results = collect([]);
        foreach ($activePaymentMethods as $payment) {
            // Prepaid only — hide Cash on Delivery
            if ($payment->code === 'cod') {
                continue;
            }

            if (
                ! in_array($payment->code, $activePaymentCodes) ||
                ! get_payment_config_info($payment->code, $shopConfig)
            ) {
                continue;
            }

            $results->push($payment);
        }

        return PaymentMethodResource::collection($results);
    }

    /**
     * Get active currencies.
     *
     * @return \Illuminate\Http\Response
     */
    public function currencies()
    {
        return $this->rememberApi('currencies', function () {
            $currencies = Currency::active()->orderBy('priority', 'asc')->get();

            return CurrencyResource::collection($currencies);
        }, null, 'config');
    }

    /**
     * Get country list resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function countries()
    {
        return $this->rememberApi('countries', function () {
            $isos = config('system.marketplace_country_isos', ['IN', 'MZ']);

            $countries = Country::select('id', 'name', 'iso_code')
                ->active()
                ->whereIn('iso_code', $isos)
                ->orderBy('name')
                ->get();

            return CountryResource::collection($countries);
        }, null, 'config');
    }

    /**
     * Display the specified resource.
     *
     * @param  string  $country
     * @return \Illuminate\Http\Response
     */
    public function states($country)
    {
        return $this->rememberApi('states:'.$country, function () use ($country) {
            $states = State::select('id', 'name', 'iso_code')
                ->where('country_id', $country)
                ->get();

            return StateResource::collection($states);
        }, null, 'config');
    }

    /**
     * Returns the details of the page with the given slug.
     *
     * @param  string  $slug  The slug of the page.
     * @return PageResource Contains page details
     */
    public function page($slug)
    {
        return $this->rememberApi('page:'.$slug, function () use ($slug) {
            $page = Page::where('slug', $slug)->firstOrFail();

            return new PageResource($page);
        }, null, 'config');
    }
}
