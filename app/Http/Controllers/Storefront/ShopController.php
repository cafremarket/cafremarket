<?php

namespace App\Http\Controllers\Storefront;

use App\Helpers\ListHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Validations\BrowseProductRequest;
use App\Models\Banner;
use App\Models\Feedback;
use App\Models\Inventory;
use App\Models\Shop;
use App\Models\Slider;
use App\Services\Hyperlocal\BuyerLocationService;
use App\Services\Hyperlocal\HyperlocalCatalogService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ShopController extends Controller
{
    /**
     * Open shop list page
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, BuyerLocationService $buyerLocation, HyperlocalCatalogService $catalog)
    {
        $buyerLocation->ensureDeliveryLocation();

        $scope = $request->get('scope', 'nearby');
        $customer = auth('customer')->user();

        if ($customer?->preferred_address_id && $request->hasAny(['lat', 'lng', 'address_text'])) {
            return redirect()->route('shops', $request->except(['lat', 'lng', 'address_text']));
        }

        if (
            ! $customer?->preferred_address_id
            && $request->filled('lat')
            && $request->filled('lng')
            && $scope !== 'all'
        ) {
            $buyerLocation->save(
                (float) $request->get('lat'),
                (float) $request->get('lng'),
                $request->get('address_text') ?: $buyerLocation->addressText(),
                $request
            );
        }

        if ($buyerLocation->hasLocation() && $scope !== 'all') {
            $nearby = $catalog->nearbyShopsWithDistance();
            $shops = $nearby->pluck('shop');
            $distances = $nearby->mapWithKeys(fn ($row) => [$row['shop']->id => $row['distance_km']]);

            return view('theme::stores_list', [
                'shops' => $shops,
                'distances' => $distances,
                'isNearby' => true,
            ]);
        }

        $shops = Shop::select('id', 'owner_id', 'slug', 'name', 'id_verified', 'phone_verified', 'address_verified', 'total_item_sold', 'total_sold_amount', 'created_at')
            ->with([
                'config',
                'logoImage',
                'owner:id,name,nice_name,email',
                'owner.avatarImage:path,imageable_id,imageable_type',
                // 'address:id,city,country_id,state_id,addressable_id,addressable_type',
                // 'address.state:id,name',
                'avgFeedback:rating,count,feedbackable_id,feedbackable_type',
            ])
            ->withCount([
                'inventories' => function ($q) {
                    $q->where('active', 1)->whereNull('parent_id');
                },
            ])
            // Keep shops page inclusive: show approved sellers even if they
            // haven't completed all "go live" requirements yet.
            ->approved()
            ->whereHas('inventories', function ($q) {
                $q->where('active', 1)->whereNull('parent_id');
            })
            ->paginate(16)
            ->appends(request()->query());

        return view('theme::stores_list', [
            'shops' => $shops,
            'distances' => [],
            'isNearby' => false,
        ]);
    }

    /**
     * Open shop page
     *
     * @param  slug  $slug
     * @return \Illuminate\Http\Response
     */
    public function show($slug = null)
    {
        if (! $slug) {
            return redirect()->route('shops');
        }

        $shop = Shop::where('slug', $slug)->approved()
            ->withCount([
                'inventories' => function ($q) {
                    $q->where('active', 1)->whereNull('parent_id');
                },
            ])
            ->firstOrFail();

        // Check shop maintenance_mode
        if ($shop->isDown()) {
            return response()->view('theme::errors.503', [], 503);
        }

        $banners = Cache::rememberForever('banners'.$shop->id, function () use ($shop) {
            return Banner::with('featureImage:path,imageable_id,imageable_type')
                ->where('shop_id', $shop->id)
                ->orderBy('order', 'asc')->get()
                ->groupBy('group_id')->toArray();
        });

        // Deal of the day;
        $deal_of_the_day = get_deal_of_the_day($shop->id);

        $featured_items = null;

        // Top Selling Items
        $top_items = ListHelper::top_selling_shop_items($shop, 10);

        // Recently Added Items
        $recent = ListHelper::latest_shop_items($shop, 10);

        // Best deal under the amount
        $deals_under = Cache::rememberForever('deals_under'.$shop->id, function () use ($shop) {
            return ListHelper::best_find_under(best_finds_under($shop->id), 20, $shop->id);
        });

        $sliders = Cache::rememberForever('sliders'.$shop->id, function () use ($shop) {
            return Slider::orderBy('order', 'asc')
                ->where('shop_id', $shop->id)
                ->with([
                    'featureImage:path,imageable_id,imageable_type',
                    'mobileImage:path,imageable_id,imageable_type',
                ])
                ->get()->toArray();
        });

        return view('theme::shop', compact('shop', 'sliders', 'banners', 'featured_items', 'top_items', 'deal_of_the_day', 'deals_under', 'recent'));
    }

    /**
     * Show all products of the given shop
     *
     * @param  slug  $slug
     * @return \Illuminate\Http\Response
     */
    public function products(BrowseProductRequest $request, $slug)
    {
        $now = Carbon::now();
        $shop = Shop::where('slug', $slug)->approved()->withCount([
            'inventories' => function ($q) {
                $q->where('active', 1)->whereNull('parent_id');
            },
        ])->firstOrFail();

        // Check shop maintenance_mode
        if ($shop->isDown()) {
            return response()->view('theme::errors.503', [], 503);
        }

        $all_products = Inventory::where('shop_id', $shop->id)
            ->whereNull('parent_id')
            ->with([
                'avgFeedback:rating,count,feedbackable_id,feedbackable_type',
                'image:path,imageable_id,imageable_type',
            ])
            ->withCount(['orders' => function (\Illuminate\Database\Eloquent\Builder $q) use ($now) {
                $q->where('order_items.created_at', '>=', $now->subHours(config('system.popular.hot_item.period', 24)));
            }])
            ->where('active', 1);

        $forPriceRange = $all_products->get();
        $min = floor($forPriceRange->min('sale_price'));
        $max = ceil($forPriceRange->max('sale_price'));
        $priceRange = compact('min', 'max');

        // Filtering occurs after priceRange has been extracted.
        // $all_products = $all_products->filter($request->all())->inRandomOrder()->get();
        if ($request->sort_by) {
            $all_products = $all_products->filter($request->all())->get();
        } else {
            $all_products = $all_products->filter($request->all())->inRandomOrder()->get();
        }

        // $new = $all_products->where('condition', trans('app.new'))->count();
        // $used = $all_products->where('condition', trans('app.used'))->count();
        // $refurbished = $all_products->where('condition', trans('app.refurbished'))->count();
        // $productConditions = compact('new', 'used', 'refurbished');
        // $hasOffers = $all_products->where('offer_price', '>', 0)->where('offer_start', '<', $now)->where('offer_end', '>', $now)->count();
        // $hasFreeShipping = $all_products->where('free_shipping', 1)->count();
        // $newArrivals = $all_products->where('created_at', '>', $now->subDays(config('system.filter.new_arrival', 7)))->count();

        // Paginate the results
        $products = $all_products->paginate(16); // PLS 15 -> 16 products per page (4 rows by 4 products)

        return view('theme::shop', compact('shop', 'products', 'priceRange'));
    }

    /**
     * Browse a store-scoped category within a shop.
     *
     * @param  string  $slug  shop slug
     * @param  string  $category  category slug
     * @return \Illuminate\Http\Response|\Illuminate\Contracts\View\View
     */
    public function category(BrowseProductRequest $request, $slug, $category)
    {
        $shop = Shop::where('slug', $slug)->approved()->withCount([
            'inventories' => function ($q) {
                $q->where('active', 1)->whereNull('parent_id');
            },
        ])->firstOrFail();

        if ($shop->isDown()) {
            return response()->view('theme::errors.503', [], 503);
        }

        $categoryModel = \App\Models\Category::where('slug', $category)
            ->where(function ($q) use ($shop) {
                $q->where('shop_id', $shop->id)->orWhereNull('shop_id');
            })
            ->with([
                'attrsList' => function ($q) {
                    $q->with('attributeValues');
                },
            ])
            ->active()
            ->firstOrFail();

        $listingsBase = $categoryModel->listings()
            ->where('inventories.active', 1)
            ->where('inventories.shop_id', $shop->id);

        $minRaw = (clone $listingsBase)->min('inventories.sale_price');
        $maxRaw = (clone $listingsBase)->max('inventories.sale_price');
        $priceRange = [
            'min' => floor((float) ($minRaw ?? 0)),
            'max' => ceil((float) ($maxRaw ?? 0)),
        ];

        $products = $listingsBase
            ->with([
                'avgFeedback:rating,count,feedbackable_id,feedbackable_type',
                'shop:id,slug,name,id_verified,phone_verified,address_verified',
                'image:path,imageable_id,imageable_type',
            ])
            ->filter($request->all())
            ->paginate(config('system.view_listing_per_page', 16))
            ->appends($request->except('page'));

        $category = $categoryModel;

        return view('theme::category', compact('shop', 'category', 'products', 'priceRange'));
    }

    /**
     * Show all reviews of the given shop
     *
     * @param  slug  $slug
     * @return \Illuminate\Http\Response
     */
    public function reviews($slug)
    {
        $shop = Shop::where('slug', $slug)->approved()->withCount([
            'inventories' => function ($q) {
                $q->where('active', 1)->whereNull('parent_id');
            },
        ])->firstOrFail();

        // Check shop maintenance_mode
        if ($shop->isDown()) {
            return response()->view('theme::errors.503', [], 503);
        }

        $reviews = Feedback::where([
            ['feedbackable_id', '=', $shop->id],
            ['feedbackable_type', '=', 'App\Models\Shop'],
        ])->with('customer:id,nice_name,name')
            ->latest()->paginate(5);

        return view('theme::shop', compact('shop', 'reviews'));
    }
}
