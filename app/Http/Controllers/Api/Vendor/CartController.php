<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Resources\CartResource;
use App\Models\Cart;
use App\Repositories\Cart\CartRepository;
use Illuminate\Http\Request;

class CartController extends Controller
{
    private $cart;

    public function __construct(CartRepository $cart)
    {
        parent::__construct();
        $this->cart = $cart;
    }

    public function index(Request $request)
    {
        $query = Cart::mine()->with('customer', 'inventories');

        if ($request->get('filter') === 'abandoned') {
            $hours = (int) config('system_settings.abandoned_cart_hours', 24);
            $query->where('updated_at', '<=', now()->subHours($hours));
        }

        $carts = $query->whereHas('customer')->latest()->paginate(
            config('mobile_app.view_listing_per_page', 8)
        );

        return CartResource::collection($carts);
    }

    public function show($id)
    {
        $cart = Cart::mine()
            ->with('customer', 'inventories.image', 'shop')
            ->findOrFail($id);

        return new CartResource($cart);
    }
}
