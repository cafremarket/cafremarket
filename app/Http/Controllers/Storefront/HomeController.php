<?php

namespace App\Http\Controllers\Storefront;

use App\Common\InventorySearch;
use App\Helpers\ListHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Validations\BrowseProductRequest;
use App\Models\Attribute;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Country;
use App\Models\Inventory;
use App\Models\Manufacturer;
use App\Models\Page;
use App\Models\Product;
use App\Models\Slider;
use App\Models\SubCategory;
use App\Services\Hyperlocal\BuyerLocationService;
use App\Services\Hyperlocal\HyperlocalCatalogService;
use App\Support\PolicyPages;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;

class HomeController extends Controller
{
    // To search in inventory
    use InventorySearch;

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request, BuyerLocationService $buyerLocation, HyperlocalCatalogService $catalog)
    {
        // Store panel users should land on their own storefront, not the marketplace homepage.
        // Skip if the request is already for that shop path (prevents redirect loops).
        if ($shopUrl = storefront_merchant_shop_url()) {
            $shopPath = trim(parse_url($shopUrl, PHP_URL_PATH) ?: '', '/');
            $current = trim($request->path(), '/');
            if ($shopPath !== '' && $current !== $shopPath && ! str_starts_with($current, $shopPath.'/')) {
                return redirect()->to($shopUrl);
            }
        }

        $buyerLocation->ensureDeliveryLocation();

        $sliders = Cache::rememberForever('sliders', function () {
            return Slider::orderBy('id', 'asc')
                ->with([
                    'featureImage:path,imageable_id,imageable_type',
                    'mobileImage:path,imageable_id,imageable_type',
                ])
                ->where('shop_id', null)
                ->get()->toArray();
        });

        $banners = Cache::rememberForever('banners', function () {
            return Banner::with('featureImage:path,imageable_id,imageable_type')
                ->whereNull('shop_id')
                ->forWeb()
                ->orderBy('id', 'asc')->get()
                ->groupBy('group_id')->toArray();
        });

        $featuredCategories = Cache::rememberForever('featured_categories_web', function () {
            return Category::active()->featured()
                ->with(['coverImage', 'featureImage'])
                ->orderBy('name', 'asc')
                ->get();
        });

        $latitude = $buyerLocation->latitude();
        $longitude = $buyerLocation->longitude();
        $buyerAddress = $buyerLocation->addressText();

        $nearbyShopsPaginator = $catalog->nearbyShopsPaginated();

        // Curated lists, then keep only products from shops deliverable to the buyer location.
        // Featured products: nearest store's products first, farthest store's products last.
        $featuredItems = $catalog->sortByShopDistance($catalog->filterInventories(get_featured_items()));
        $dealOfTheDay = $catalog->filterInventories(get_deal_of_the_day())->values();

        // Recently Added Products — capped at 20 on the web (no "load more" here;
        // the app shows the same feed with infinite scroll via /api/recently-added-products).
        $recentlyAddedItems = $catalog->filterInventories(
            ListHelper::latest_available_items(20)
        )->values();

        return view('theme::index', compact(
            'banners',
            'sliders',
            'nearbyShopsPaginator',
            'latitude',
            'longitude',
            'buyerAddress',
            'featuredItems',
            'featuredCategories',
            'dealOfTheDay',
            'recentlyAddedItems',
        ));
    }

    /**
     * Browse category based products
     *
     * @param  string  $slug
     * @return \Illuminate\View\View
     */
    public function browseCategory(BrowseProductRequest $request, $slug, $sortby = null)
    {
        if ($gate = hyperlocal_browse_gate_view()) {
            return $gate;
        }

        $catalog = app(HyperlocalCatalogService::class);

        $category = SubCategory::where('slug', $slug)
            ->with([
                'category' => function ($q) {
                    $q->select(['id', 'slug', 'name'])->active();
                },
                'attrsList' => function ($q) {
                    $q->with('attributeValues');
                },
            ])
            ->active()->firstOrFail();

        // Avoid loading every listing into memory just for min/max price.
        $listingsBase = $catalog->scopeInventoryQuery($category->listings()->available());
        $minRaw = (clone $listingsBase)->min('inventories.sale_price');
        $maxRaw = (clone $listingsBase)->max('inventories.sale_price');
        $priceRange = [
            'min' => floor((float) ($minRaw ?? 0)),
            'max' => ceil((float) ($maxRaw ?? 0)),
        ];

        $products = $listingsBase
            ->with([
                'reviewSummary:rating,count,reviewable_id,reviewable_type',
                'shop:id,slug,name,id_verified,phone_verified,address_verified',
                'image:path,imageable_id,imageable_type',
            ])
            ->filter($request->all())
            ->paginate(config('system.view_listing_per_page', 16))
            ->appends($request->except('page'));

        return view('theme::category', compact('category', 'products', 'priceRange'));
    }

    /**
     * Browse listings by category sub group
     *
     * @param  string  $slug
     * @return \Illuminate\View\View
     */
    public function browseCategorySubGrp(BrowseProductRequest $request, $slug, $sortby = null)
    {
        if ($gate = hyperlocal_browse_gate_view()) {
            return $gate;
        }

        $catalog = app(HyperlocalCatalogService::class);
        $now = Carbon::now();
        $min = null;
        $max = null;
        $new = null;
        $used = null;
        $refurbished = null;

        $category = Category::where('slug', $slug)
            ->with([
                'subCategories' => function (\Illuminate\Database\Eloquent\Relations\HasMany $q) {
                    $q->select(['id', 'slug', 'category_id', 'name'])->whereHas('listings')->active();
                },
                'subCategories.listings' => function (\Illuminate\Database\Eloquent\Relations\BelongsToMany $d) use ($now, $request, &$min, &$max, &$new, &$used, &$refurbished) {
                    /** @var \App\Models\Inventory $d */
                    $all_results = $d->available()->get();
                    $min = floor($all_results->min('sale_price'));
                    $max = ceil($all_results->max('sale_price'));

                    $results2 = $d->available()->filter($request->all())->withCount([
                        'orders' => function (\Illuminate\Database\Eloquent\Builder $query) use ($now) {
                            $query->where('order_items.created_at', '>=', $now->subHours(config('system.popular.hot_item.period', 24)));
                        },
                    ])
                        ->with([
                            'reviewSummary:rating,count,reviewable_id,reviewable_type',
                            'shop:id,slug,name,id_verified,phone_verified,address_verified',
                            'image:path,imageable_id,imageable_type',
                        ])->get();
                },
            ])
            ->active()->firstOrFail();

        /** @var \Illuminate\Database\Eloquent\Builder $all_products * */
        $all_products = prepareFilteredListingsNew($request, $category->subCategories);

        if ($catalog->isEnabled()) {
            $all_products = $catalog->filterInventories($all_products);
        }

        $priceRange = compact('min', 'max');

        // Paginate the results
        $products = $all_products->paginate(config('system.view_listing_per_page', 16))
            ->appends($request->except('page'));

        return view('theme::category_sub_group', compact('category', 'products', 'priceRange'));
    }

    /**
     * Retrieves and displays the details of a product based on its slug.
     *
     * @param  string  $slug  The slug of the product.
     * @return \Illuminate\View\View The view displaying the product details.
     */
    public function product($shop, $slug)
    {
        $item = Inventory::where('slug', $slug)
            ->whereHas('shop', function ($q) use ($shop) {
                $q->where('slug', $shop);
            })
            ->withCount('reviews')->available()->withTrashed()->first();

        if (! $item) {
            $item = Inventory::where('slug', $slug)
                ->whereHas('shop', function ($q) use ($shop) {
                    $q->where('slug', $shop);
                })
                ->withCount('reviews')->withTrashed()->first();
        }

        if (! $item) {
            $fallback = Inventory::where('slug', $slug)->with('shop:id,slug')->first();
            if ($fallback) {
                return redirect()->to(storefront_product_url($fallback), 301);
            }

            return view('theme::exceptions.item_not_available');
        }

        $item->load([
            'product' => function ($q) use ($item) {
                $q->select('id', 'brand', 'model_number', 'mpn', 'gtin', 'gtin_type', 'origin_country', 'slug', 'description', 'video_path', 'downloadable', 'manufacturer_id', 'sale_count', 'created_at')
                    ->with([
                        'subCategories:id,slug,name,category_id',
                    ])
                    ->withCount(['inventories' => function ($query) use ($item) {
                        $query->where('shop_id', '!=', $item->shop_id)
                            ->whereNull('parent_id')
                            ->available();
                    }]);
            },
            'attributeValues' => function ($q) {
                $q->select('id', 'attribute_values.attribute_id', 'value', 'color')
                    ->with('attribute:id,name,attribute_type_id');
            },
            'shop' => function ($q) {
                $q->withCount([
                    'inventories' => function ($query) {
                        $query->whereNull('parent_id');
                    },
                ])
                    ->with([
                        'reviewSummary:rating,count,reviewable_id,reviewable_type',
                        'latestReviews' => function ($q) {
                            $q->with('customer:id,nice_name,name');
                        },
                    ]);
            },
            'latestReviews' => function ($q) {
                $q->with('customer:id,nice_name,name');
            },
            'reviewSummary:rating,count,reviewable_id,reviewable_type',
            'images:id,path,imageable_id,imageable_type',
            'tags:id,name',
        ]);

        // Auction listings
        if (is_incevio_package_loaded('auction')) {
            $item->loadCount('bids');
        }

        $this->update_recently_viewed_items($item); // update_recently_viewed_items

        $variants = ListHelper::variants_of_product($item, $item->shop_id);

        $attr_pivots = DB::table('attribute_inventory')
            ->select('attribute_id', 'inventory_id', 'attribute_value_id')
            ->whereIn('inventory_id', $variants->pluck('id'))->get();

        $item_attrs = $attr_pivots->where('inventory_id', $item->id)
            ->pluck('attribute_value_id')->toArray();

        // Parent listing often has no attributes; preselect the first attributed SKU
        // so Colour/Size options render selected and cart URLs point at a real variant.
        if (empty($item_attrs) && $variants->isNotEmpty()) {
            $defaultVariant = $variants->first();
            $item_attrs = $attr_pivots->where('inventory_id', $defaultVariant->id)
                ->pluck('attribute_value_id')->toArray();
        }

        $attributes = Attribute::select('id', 'name', 'attribute_type_id')
            ->whereIn('id', $attr_pivots->pluck('attribute_id'))
            ->with(['attributeValues' => function ($query) use ($attr_pivots) {
                $query->whereIn('id', $attr_pivots->pluck('attribute_value_id'))->orderBy('value');
            }])
            ->orderBy('name')->get();

        $related = ListHelper::related_products($item);
        $linked_items = ListHelper::linked_items($item);
        $alternative_items = ListHelper::alternative_items($item);

        // Country list for ship_to dropdown
        $business_areas = Cache::rememberForever('countries_cached', function () {
            return Country::select('id', 'name', 'iso_code')->orderBy('name', 'asc')->get();
        });

        if (is_incevio_package_loaded('wholesale')) {
            $item->wholesale_prices = get_wholesale_item_prices($item->id);
        }

        $canReviewProduct = false;
        $myProductReview = null;

        if (\Illuminate\Support\Facades\Auth::guard('customer')->check()) {
            $customer = \Illuminate\Support\Facades\Auth::guard('customer')->user();
            $myProductReview = \App\Models\Review::where('customer_id', $customer->id)
                ->where('reviewable_type', Inventory::class)
                ->where('reviewable_id', $item->id)
                ->first();

            $canReviewProduct = (bool) app(\App\Services\Review\ReviewEligibilityService::class)
                ->canReviewProduct($customer, $item);
        }

        return view('theme::product', compact('item', 'variants', 'attributes', 'item_attrs', 'related', 'linked_items', 'business_areas', 'alternative_items', 'canReviewProduct', 'myProductReview'));
    }

    /**
     * Open product quick review modal
     *
     * @param  string  $slug
     * @return \Illuminate\View\View| string HTML of rendered view
     */
    public function quickViewItem($shop, $slug)
    {
        $item = Inventory::where('slug', $slug)
            ->whereHas('shop', function ($q) use ($shop) {
                $q->where('slug', $shop);
            })
            ->available()
            ->with([
                'images:path,imageable_id,imageable_type',
                'product' => function ($q) {
                    $q->select('id', 'slug', 'downloadable', 'video_path')
                        ->withCount(['inventories' => function ($query) {
                            $query->whereNull('parent_id')->available();
                        }]);
                },
            ])
            ->withCount('reviews')->firstOrFail();

        if (is_incevio_package_loaded('wholesale')) {
            $item->wholesale_prices = get_wholesale_item_prices($item->id);
        }

        $this->update_recently_viewed_items($item); // update recently viewed items

        $variants = ListHelper::variants_of_product($item, $item->shop_id);

        $attr_pivots = DB::table('attribute_inventory')
            ->select('attribute_id', 'inventory_id', 'attribute_value_id')
            ->whereIn('inventory_id', $variants->pluck('id'))->get();

        $attributes = Attribute::select('id', 'name', 'attribute_type_id')
            ->whereIn('id', $attr_pivots->pluck('attribute_id'))
            ->with([
                'attributeValues' => function ($query) use ($attr_pivots) {
                    $query->whereIn('id', $attr_pivots->pluck('attribute_value_id'))->orderBy('value');
                },
            ])
            ->orderBy('name')->get();

        $item_attrs = $attr_pivots->where('inventory_id', $item->id)
            ->pluck('attribute_value_id')->toArray();

        if (empty($item_attrs) && $variants->isNotEmpty()) {
            $item_attrs = $attr_pivots->where('inventory_id', $variants->first()->id)
                ->pluck('attribute_value_id')->toArray();
        }

        return view('theme::modals.quickview', compact('item', 'attributes', 'item_attrs', 'variants'))->render();
    }

    /**
     * Open shop page
     *
     * @param  string  $slug
     * @return \Illuminate\View\View
     */
    public function offers($shop, $slug)
    {
        $product = Product::where('slug', $slug)
            ->whereHas('shop', function ($q) use ($shop) {
                $q->where('slug', $shop);
            })
            ->with([
                'inventories' => function ($q) {
                    $q->available();
                },
                'inventories.attributeValues.attribute',
                'inventories.reviewSummary:rating,count,reviewable_id,reviewable_type',
                'inventories.shop.reviews:rating,reviewable_id,reviewable_type',
                'inventories.shop.image:path,imageable_id,imageable_type',
            ])
            ->firstOrFail();

        return view('theme::offers', compact('product'));
    }

    /**
     * Brand listing page removed from the marketplace.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function all_brands()
    {
        return redirect()->route('homepage');
    }

    /**
     * Brand page removed from the marketplace.
     *
     * @param  string  $slug
     * @return \Illuminate\Http\RedirectResponse
     */
    public function brand(BrowseProductRequest $request, $slug)
    {
        return redirect()->route('homepage');
    }

    /**
     * Brand products page removed from the marketplace.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function brandProducts(BrowseProductRequest $request, string $slug)
    {
        return redirect()->route('homepage');
    }

    /**
     * Display the category list page.
     *
     * @return \Illuminate\View\View
     */
    public function categories()
    {
        return view('theme::categories');
    }

    /**
     * Display the specified resource.
     *
     * @param  string  $slug
     * @return \Illuminate\View\View
     */
    public function openPage($slug)
    {
        $page = Page::where('slug', $slug)->firstOrFail();

        if (PolicyPages::isPolicySlug($page->slug) && PolicyPages::isPlaceholder($page->content)) {
            $page->content = PolicyPages::resolveContent($page->slug, $page->content);
        }

        return view('theme::page', compact('page'));
    }

    /**
     * Push product ID to session for the recently viewed items section
     *
     * @param  [type] $item [description]
     */
    private function update_recently_viewed_items($item)
    {
        $items = Session::get('products.recently_viewed_items', []);

        if (! in_array($item->getKey(), $items)) {
            Session::push('products.recently_viewed_items', $item->getKey());
        } else {
            $key = array_search($item->getKey(), $items);

            unset($items[$key]);

            Session::push('products.recently_viewed_items', $item->getKey());
        }

        Cache::forget('recently_viewed_items');
    }
}
