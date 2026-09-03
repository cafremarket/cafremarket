<?php

namespace App\Http\Controllers\Storefront;

use App\Common\ShoppingCart;
use App\Helpers\ListHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Validations\DirectCheckoutRequest;
use App\Models\Cart;
use App\Models\Country;
use App\Models\PaymentMethod;
use App\Models\Shop;
use App\Models\State;
use App\Models\SystemConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckoutController extends Controller
{
    use ShoppingCart;

    /**
     * Handles the Cart checkout process.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function checkout(Request $request, Cart $cart)
    {
        return redirect()->route('cart.index');
    }

    /**
     * Direct checkout with the item/cart
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $slug
     * @return \Illuminate\Http\RedirectResponse
     */
    public function directCheckout(DirectCheckoutRequest $request, $slug)
    {
        $cart = $this->addToCart($request, $slug);

        if ($cart->status() == 200) {
            $cartData = $cart->getData();

            if (is_object($cartData) && property_exists($cartData, '0') && isset($cartData->{'0'}->id)) {
                return redirect()->route('cart.index', $cartData->{'0'}->id);
            }
        }

        if ($cart->status() == 444) {
            $cartData = $cart->getData();

            return redirect()->route('cart.index', $cartData->cart_id ?? null);
        }

        $payload = $cart->getData(true);
        $message = is_array($payload) ? ($payload['message'] ?? trans('theme.notify.failed')) : trans('theme.notify.failed');

        if ($cart->status() === 403 || $cart->status() === 422) {
            return redirect()->route('cart.index')->with('warning', $message);
        }

        return redirect()->back()->with('warning', $message);
    }

    /**
     * Redirects the customer to the cart page if they are not approved yet.
     *
     * @return \Illuminate\Http\RedirectResponse|null
     */
    private function redirectIfCustomerNotApproved()
    {
        $user = Auth::guard('customer')->user();

        if ($user instanceof Customer ? ! $user->isApproved() : false) {
            return redirect()->route('cart.index')->with('warning', trans('help.account_needs_approval'));
        }
    }
}
