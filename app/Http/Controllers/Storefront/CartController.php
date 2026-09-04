<?php

namespace App\Http\Controllers\Storefront;

use App\Common\ShoppingCart;
use App\Http\Controllers\Controller;
use App\Helpers\ListHelper;
use App\Models\Cart;
use App\Models\Country;
use App\Models\Coupon;
use App\Models\PaymentMethod;
use App\Models\State;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    use ShoppingCart;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, $expressId = null)
    {
        $carts = $this->getShoppingCarts();

        $carts->load([
            'shop' => function ($q) {
                $q->with('config', 'logoImage:path,imageable_id,imageable_type', 'primaryAddress', 'addresses')->active();

                if (is_incevio_package_loaded('packaging')) {
                    $q->with(['packagings' => function ($query) {
                        $query->active();
                    }]);
                }
            },
            'inventories.images:path,imageable_id,imageable_type',
            'shippingZone',
            'shippingAddress',
            'state:id,name',
            'country:id,name',
            'inventories.image',
            'coupon:id,shop_id,name,code,value,min_order_amount,type',
        ]);

        $business_areas = Country::select('id', 'name', 'iso_code')->orderBy('name', 'asc')->get();

        $geoip = geoip(get_visitor_IP());

        $geoip_country = $business_areas->where('iso_code', $geoip->iso_code)->first()
            ?? $business_areas->where('iso_code', get_default_geoip_country_iso())->first()
            ?? $business_areas->first();

        $geoip_state = null;
        if ($geoip_country && $geoip->state) {
            $geoip_state = State::select('id', 'name', 'iso_code', 'country_id')
                ->where('iso_code', $geoip->state)
                ->where('country_id', $geoip_country->id)
                ->first();
        }

        $shipping_zones = [];
        $shipping_options = [];

        // Prepare shipping info (location-based: free / fixed / km)
        $calculator = app(\App\Services\Shipping\ShippingCalculator::class);

        foreach ($carts as $cart) {
            $country_id = $cart->ship_to_country_id ?? optional($geoip_country)->id;
            $state_id = $cart->ship_to_state_id ?? optional($geoip_state)->id;

            // Keep zone only for tax resolution when available
            $shipping_zones[$cart->id] = get_shipping_zone_of($cart->shop_id, $country_id, $state_id);

            if (! $cart->ship_to_country_id) {
                $cart->ship_to_country_id = $country_id;
                $cart->ship_to_state_id = $state_id;
            }

            if (isset($shipping_zones[$cart->id]->id)) {
                $cart->shipping_zone_id = $shipping_zones[$cart->id]->id;
                $cart->taxrate = $cart->shippingZone ? optional($cart->shippingZone->tax)->taxrate : $cart->taxrate;
                $cart->taxes = $cart->get_tax_amount();
            }

            if (! $cart->is_digital) {
                $calculator->applyToCart($cart);
                $shipping_options[$cart->id] = $calculator->shippingOptionsPayload($cart);
            } else {
                $cart->shipping = 0;
                $cart->shipping_rate_id = null;
                $shipping_options[$cart->id] = collect();
            }

            $cart->handling = $cart->get_handling_cost();
            $cart->taxes = $cart->get_tax_amount();
            $cart->discount = $cart->get_discounted_amount();
            $cart->grand_total = $cart->calculate_grand_total();
            $cart->save();
        }

        $customer = Auth::guard('customer')->check() ? Auth::guard('customer')->user() : null;
        if ($customer) {
            $customer->load(['addresses.country', 'addresses.state']);
        }

        $paymentMethods = PaymentMethod::active()->get();

        foreach ($carts as $cart) {
            if ($cart->shop) {
                $cart->shop->load(['paymentMethods' => function ($q) {
                    $q->active();
                }]);

                if (optional($cart->shop->config)->isPickupEnabled()) {
                    $cart->shop->load('warehouses.address');
                }
            }
        }

        $this->annotateCartDeliveryRange($carts);

        // One-store checkout: only the selected cart is checked out at a time.
        $wantedId = $expressId ? (int) $expressId : (int) $request->query('cart');
        $activeCart = $wantedId
            ? $carts->firstWhere('id', $wantedId)
            : null;
        if (! $activeCart && $carts->isNotEmpty()) {
            $activeCart = $carts->first(fn ($c) => empty($c->out_of_range) && empty($c->needs_delivery_location))
                ?? $carts->first();
        }
        $expressId = $activeCart?->id;

        $states = [];
        if ($activeCart && $activeCart->ship_to_country_id) {
            $states = ListHelper::states($activeCart->ship_to_country_id);
        }

        if (is_incevio_package_loaded('packaging')) {
            $carts->load('shippingPackage');

            if (is_incevio_package_loaded('dynamic-currency')) {    // Prepare packaging info
                foreach ($carts as $cart) {
                    $cart->shop->packagings->map(function ($rate) {
                        $rate->cost = get_dynamic_currency_value($rate->cost);

                        return $rate;
                    });
                }
            }

            $platformDefaultPackaging = getPlatformDefaultPackaging();

            return view('theme::cart', compact('carts', 'activeCart', 'business_areas', 'shipping_zones', 'shipping_options', 'platformDefaultPackaging', 'expressId', 'customer', 'paymentMethods', 'states'));
        }

        return view('theme::cart', compact('carts', 'activeCart', 'business_areas', 'shipping_zones', 'shipping_options', 'expressId', 'customer', 'paymentMethods', 'states'));
    }

    /**
     * Flag each cart as in/out of the shop service radius for the buyer location.
     */
    protected function annotateCartDeliveryRange($carts): void
    {
        $catalog = app(\App\Services\Hyperlocal\HyperlocalCatalogService::class);
        $buyer = app(\App\Services\Hyperlocal\BuyerLocationService::class);
        $buyer->ensureDeliveryLocation();

        foreach ($carts as $cart) {
            $cart->out_of_range = false;
            $cart->needs_delivery_location = false;
            $cart->delivery_distance_km = null;
            $cart->service_radius_km = null;

            if ($cart->is_digital || ! $catalog->isEnabled() || ! $cart->shop) {
                continue;
            }

            $lat = $buyer->latitude();
            $lng = $buyer->longitude();

            // Prefer explicit ship-to address coordinates when present.
            $shipTo = $cart->relationLoaded('shippingAddress')
                ? $cart->shippingAddress
                : ($cart->ship_to ? \App\Models\Address::find($cart->ship_to) : null);
            if ($shipTo && $shipTo->latitude && $shipTo->longitude) {
                $lat = (float) $shipTo->latitude;
                $lng = (float) $shipTo->longitude;
            }

            $shop = $cart->shop;
            $store = $shop->storeAddress();
            $radius = (float) ($shop->service_radius_km ?: config('hyperlocal.default_shop_service_radius_km', 5));
            $cart->service_radius_km = $radius;

            if (! $lat || ! $lng) {
                $cart->needs_delivery_location = true;

                continue;
            }

            if (! $store || ! $store->latitude || ! $store->longitude) {
                $cart->out_of_range = true;

                continue;
            }

            $distance = app(\App\Services\Geo\DistanceService::class)->distanceKm(
                (float) $store->latitude,
                (float) $store->longitude,
                (float) $lat,
                (float) $lng
            );
            $cart->delivery_distance_km = round($distance, 1);
            $cart->out_of_range = $distance > $radius;
        }
    }

    /**
     * Update the cart and redirected to checkout page.
     *
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Cart $cart)
    {
        if (! crosscheckCartOwnership($request, $cart)) {
            return response(trans('theme.notify.please_login_to_checkout'), 401);
        }

        $cart = crosscheckAndUpdateOldCartInfo($request, $cart);

        return response(trans('theme.notify.cart_updated'), 200);
    }

    /**
     * Remove item from cart.
     *
     * @return \Illuminate\Http\Response
     */
    public function remove(Request $request)
    {
        $cart = Cart::findOrFail($request->cart);

        $item = DB::table('cart_items')->where([
            'cart_id' => $request->cart,
            'inventory_id' => $request->item,
        ])->delete();

        // Delete item from cart_items table
        if ($item) {
            // Update or delate cart
            if ($item_count = $cart->inventories->count()) {
                $cart->fill([
                    'quantity' => $cart->inventories->sum('quantity'),
                    'item_count' => $item_count,
                ])->save();
            } else {
                // Delete the cart
                $cart->forceDelete();

                // Remove from cookie if exist
                $this->removeFromCookie($cart->id);
            }

            return response('Item removed', 200);
        }

        return response('Item remove failed!', 404);
    }

    /**
     * validate coupon.
     *
     * @return \Illuminate\Http\Response
     */
    public function validateCoupon(Request $request)
    {
        $coupon = Coupon::active()->where([
            ['code', $request->coupon],
            ['shop_id', $request->shop],
        ])->withCount(['orders', 'customerOrders'])->first();

        if (! $coupon) {
            return response('Coupon not found', 404);
        }

        if (! $coupon->isLive() || ! $coupon->isValidCustomer()) {
            return response('Coupon not valid', 403);
        }

        if (! $coupon->isValidZone($request->zone)) {
            return response('Coupon not valid for shipping area', 443);
        }

        if (! $coupon->hasQtt()) {
            return response('Coupon qtt limit exit', 444);
        }

        // Get the cart
        $cart = Cart::find($request->cart);

        if (! $cart) {
            return response('Cart not found', 445);
        }

        if ($coupon->min_order_amount && $cart->total < $coupon->min_order_amount) {
            return response()
                ->json([
                    'message' => trans('theme.notify.coupon_min_order_value'),
                ], 403);
        }

        // Set coupon_id to the cart
        $cart->coupon_id = $coupon->id;

        // Get discounted amount
        $cart->discount = $cart->get_discounted_amount();

        // When the coupon value is bigger/equal of cart total
        if ($cart->discount >= $cart->total) {
            $cart->discount = $cart->total;
            $coupon->value = $cart->total;
        }

        // Update cart
        $cart->grand_total = $cart->calculate_grand_total();
        $cart->save();

        // Unset some un-important values
        unset($coupon->description, $coupon->quantity, $coupon->quantity_per_customer, $coupon->starting_time, $coupon->ending_time, $coupon->active);

        return response()->json($coupon->toArray());
    }

    /**
     * Remove from cookie if exist
     *
     * @param  int  $cartId
     * @return void
     */
    public function removeFromCookie($cartId)
    {
        $cartIds = cart_ids_from_cookie();

        $key = array_search($cartId, $cartIds);

        if ($key !== false) {
            unset($cartIds[$key]);

            $cookieValue = implode(',', $cartIds);

            setcookie('cart_ids', $cookieValue, time() + (60 * 24 * 7), '/');
        }
    }
}
