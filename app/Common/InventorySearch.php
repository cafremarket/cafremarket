<?php

namespace App\Common;

use App\Helpers\ListHelper;
use App\Http\Requests\Validations\ProductSearchRequest;
use App\Http\Resources\ListingResource;
use App\Models\Category;
use App\Models\CategoryGroup;
use App\Models\CategorySubGroup;
use App\Models\Inventory;
use Carbon\Carbon;

trait InventorySearch
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function search(ProductSearchRequest $request)
    {
        $term = trim((string) $request->input('q', ''));

        if ($term === '') {
            $items = Inventory::query()
                ->whereHas('shop', function ($q2) {
                    $q2->where('active', true);
                })
                ->active()
                ->whereNull('parent_id')
                ->get();
        } else {
            $query = Inventory::search($term)
                ->query(function ($q1) {
                    $q1->whereHas('shop', function ($q2) {
                        $q2->where('active', true);
                    })
                        ->active()
                        ->where('parent_id', null);
                });

            $items = config('scout.driver') == 'tntsearch' ? $query->paginate(0) : $query->get();
        }

        $items->load([
            'shop:id,slug,name,current_billing_plan,trial_ends_at,active',
            'shop.config:shop_id,maintenance_mode',
            'shop.currentSubscription',
            'shop.address.state',
            'image:path,imageable_id,imageable_type',
        ]);

        // Keep results only from active shops
        $items = $items->filter(function ($item) {
            return $item->shop && $item->shop->canGoLive();
        });

        $now = Carbon::now();
        $category = null;

        // When search within a category
        if ($request->has('in')) {
            $category = Category::where('slug', $request->input('in'))
                ->with('attrsList.attributeValues')->active()->firstOrFail();

            $listings = $category->listings()->available()->get();

            $items = $items->intersect($listings);
        } elseif ($request->has('insubgrp') && ($request->input('insubgrp') != 'all')) {
            $category = CategorySubGroup::where('slug', $request->input('insubgrp'))
                ->active()->firstOrFail();

            $listings = prepareFilteredListings($request, $category);

            $items = $items->intersect($listings);
        } elseif ($request->has('ingrp')) {
            $category = CategoryGroup::where('slug', $request->input('ingrp'))
                ->active()->firstOrFail();

            $listings = prepareFilteredListings($request, $category);

            $items = $items->intersect($listings);
        }

        // Attributes for filters
        $brands = ListHelper::get_unique_brand_names_from_listings($items);
        $priceRange = get_price_ranges_from_listings($items);

        if ($request->has('free_shipping')) {
            $items = $items->where('free_shipping', 1);
        }

        if ($request->has('auction')) {
            $items = $items->where('auctionable', 1);
        }

        if ($request->has('new_arrivals')) {
            $items = $items->where('created_at', '>', $now->subDays(config('system.filter.new_arrival', 7)));
        }

        if ($request->has('has_offers')) {
            $items = $items->where('offer_price', '>', 0)
                ->where('offer_start', '<', $now)
                ->where('offer_end', '>', $now);
        }

        if ($request->has('sort_by')) {
            switch ($request->get('sort_by')) {
                case 'newest':
                    $items = $items->sortByDesc('created_at');
                    break;

                case 'oldest':
                    $items = $items->sortBy('created_at');
                    break;

                case 'price_asc':
                    $items = $items->sortBy('sale_price');
                    break;

                case 'price_desc':
                    $items = $items->sortByDesc('sale_price');
                    break;

                case 'best_match':
                default:
                    break;
            }
        }

        if ($request->has('condition')) {
            $items = $items->whereIn('condition', array_keys($request->input('condition')));
        } elseif ($request->has('item_condition')) {
            $items = $items->whereIn('condition', $request->input('item_condition'));
        }

        if ($request->has('price')) {
            $price = explode('-', $request->input('price'));
            $items = $items->where('sale_price', '>=', $price[0])->where('sale_price', '<=', $price[1]);
        }

        if ($request->has('price_min')) {
            $items = $items->where('sale_price', '>=', $request->input('price_min'));
        }

        if ($request->has('price_max')) {
            $items = $items->where('sale_price', '<=', $request->input('price_max'));
        }

        if ($request->has('brand')) {
            $items = $items->whereIn('brand', array_keys($request->input('brand')));
        }

        // Search by city/area text (location) and province/state name.
        if ($request->filled('location')) {
            $needle = mb_strtolower(trim((string) $request->input('location')));
            $items = $items->filter(function ($item) use ($needle) {
                $address = optional(optional($item->shop)->address);
                $city = mb_strtolower((string) ($address->city ?? ''));
                $line1 = mb_strtolower((string) ($address->address_line_1 ?? ''));
                $zip = mb_strtolower((string) ($address->zip_code ?? ''));
                $stateName = mb_strtolower((string) optional($address->state)->name);
                $stateIso = mb_strtolower((string) optional($address->state)->iso_code);

                return str_contains($city, $needle)
                    || str_contains($line1, $needle)
                    || str_contains($zip, $needle)
                    || str_contains($stateName, $needle)
                    || str_contains($stateIso, $needle);
            });
        }

        if ($request->filled('province')) {
            $needle = mb_strtolower(trim((string) $request->input('province')));
            $items = $items->filter(function ($item) use ($needle) {
                $address = optional(optional($item->shop)->address);
                $stateName = mb_strtolower((string) optional($address->state)->name);
                $stateIso = mb_strtolower((string) optional($address->state)->iso_code);

                return str_contains($stateName, $needle)
                    || str_contains($stateIso, $needle);
            });
        }

        // Filter by shipping zone coverage: shops that deliver to this state/region.
        if ($request->filled('state_id')) {
            $stateId = (int) $request->input('state_id');
            $items->loadMissing([
                'shop.shippingZones' => function ($q) {
                    $q->where('active', 1);
                },
            ]);

            $items = $items->filter(function ($item) use ($stateId) {
                return $item->shop && shop_ships_to_state($item->shop, $stateId);
            });
        }

        $defaultCountryId = $request->input('country_id');
        if ($defaultCountryId === null || $defaultCountryId === '') {
            $defaultCountryId = config('system_settings.address_default_country');
        }

        $searchCountries = ListHelper::active_business_areas();
        if ($searchCountries->isEmpty()) {
            $searchCountries = ListHelper::countries();
        }

        $searchStates = ListHelper::states($defaultCountryId);

        $products = $items->paginate(config('system.view_listing_per_page', 15));

        // For APIs
        if ($request->is('api/*') && $request->acceptsJson()) {
            // Load avg rating
            $products = $products->load('avgFeedback:rating,count,feedbackable_id');

            return ListingResource::collection($products);
        }

        // For Web
        $products->load([
            'product' => function ($q) {
                $q->select('id')->with([
                    'categories:id,name,slug,category_sub_group_id',
                    'categories.subGroup:id,name,slug,category_group_id',
                    'categories.subGroup.group:id,name,slug',
                ]);
            },
            'avgFeedback:rating,count,feedbackable_id,feedbackable_type',
            'images:path,imageable_id,imageable_type',
        ]);

        return view('theme::search_results', compact('products', 'category', 'brands', 'priceRange', 'searchCountries', 'searchStates'));
    }
}
