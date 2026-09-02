<?php

namespace App\Http\Controllers\Storefront;

use App\Helpers\ListHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Validations\CreateAddressRequest;
use App\Http\Requests\Validations\SelfAddressDeleteRequest;
use App\Http\Requests\Validations\SelfAddressUpdateRequest;
use App\Http\Requests\Validations\SelfAvatarUpdateRequest;
use App\Http\Requests\Validations\SelfPasswordUpdateRequest;
use App\Models\Address;
use App\Models\Customer;
// use App\Events\Profile\ProfileUpdated;
// use App\Events\Profile\PasswordUpdated;
use App\Models\Merchant;
use App\Models\Wishlist;
use App\Services\Geo\GeocodeService;
use App\Services\Hyperlocal\BuyerLocationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

class AccountController extends Controller
{
    /**
     * Show the customer dashboard.
     *
     * @return Response
     */
    public function index($tab = 'dashboard')
    {
        if (! method_exists($this, $tab)) {
            abort(404);
        }

        // Call the methods dynamically to load needed models
        $$tab = $this->$tab();

        return view('theme::dashboard', compact('tab', $tab));
    }

    /**
     * Load dashboard content
     *
     * @return mix
     */
    private function dashboard()
    {
        $data = Customer::where('id', Auth::guard('customer')->user()->id)
            ->with([
                'orders' => function ($query) {
                    $query->select(['id', 'customer_id', 'shop_id', 'order_number', 'currency_id', 'item_count', 'grand_total', 'order_status_id', 'created_at'])
                        ->with(['shop:id,slug,name', 'shop.image'])->latest()->take(5);
                },
                'wishlists' => function ($query) {
                    $query->with('inventory:id,slug,title', 'inventory.images')->latest()->take(5);
                },
            ])
            ->withCount([
                'orders',
                'wishlists',
                'messages' => function ($query) {
                    $query->unread();
                },
                'disputes' => function ($query) {
                    $query->open();
                },
            ])->first();

        $data->loadCount('coupons');

        if (is_incevio_package_loaded('auction')) {
            $data->load('bids');
            $data->loadCount('bids');
        }

        return $data;
    }

    /**
     * Return orders
     *
     * @return collection
     */
    private function orders()
    {
        return Auth::guard('customer')->user()->orders()
            ->when(request()->q, function (Builder $query) {
                $query->where('order_number', 'like', '%'.request()->q.'%');
            })
            ->with([
                'shop:id,name,slug',
                'inventories',
                'inventories.image:path,imageable_id,imageable_type',
                'inventories.attachments',
                'cancellation',
                'dispute',
            ])
            ->paginate(10);
    }

    /**
     * Return digital orders
     *
     * @return collection
     */
    // private function downloadables()
    // {
    //     $orders = Auth::guard('customer')->user()->downloadables()
    //         ->when(request()->q, function (Builder $query) {
    //             $query->where('order_number', 'like', '%' . request()->q . '%');
    //         })
    //         ->with([
    //             'shop:id,name,slug',
    //             'inventories:id,title,slug,product_id,download_limit',
    //             'inventories.image:path,imageable_id,imageable_type',
    //         ])
    //         ->paginate(10);

    //     return $orders;
    // }

    /**
     * Return inbox
     *
     * @return collection
     */
    private function messages()
    {
        return Auth::guard('customer')->user()->messages()
            ->with(['shop:id,name,slug', 'shop.image:path,imageable_id,imageable_type', 'item:id,slug,sku', 'order:id,order_number', 'lastReply:reply,read,updated_at,repliable_id'])
            // ->orderBy('created_at', 'desc')
            ->withCount('replies', 'attachments')
            ->paginate(10);
    }

    /**
     * Return wishlist
     *
     * @return collection
     */
    private function wishlist()
    {
        return Wishlist::mine()
            ->whereHas('inventory', function ($q) {
                $q->available();
            })->with([
                'inventory',
                'inventory.feedbacks:rating,feedbackable_id,feedbackable_type',
                'inventory.images:path,imageable_id,imageable_type',
            ])->paginate(10);
    }

    /**
     * Return disputes
     *
     * @return collection
     */
    private function disputes()
    {
        return Auth::guard('customer')->user()->disputes()
            ->with([
                'shop:id,name,slug',
                'order.inventories:id,product_id,slug',
                'order.inventories.product:id,slug',
                'order.inventories.image:path,imageable_id,imageable_type',
            ])
            ->paginate(10);
    }

    /**
     * Return coupons
     *
     * @return collection
     */
    private function coupons()
    {
        return Auth::guard('customer')->user()->coupons()
            ->with('shop:id,name,slug')->paginate(20);
    }

    /**
     * Return wishlist
     *
     * @return collection
     */
    private function events()
    {
        $customer = Auth::user()->id;

        $events = Customer::find($customer)->events->unique();

        return $events;
    }

    /**
     * Return account info
     *
     * @return collection
     */
    private function addresses()
    {
        // Supply important data to the views
        View::share('address_types', ListHelper::address_types());
        View::share('countries', ListHelper::countries());

        return Auth::guard('customer')->user();
    }

    /**
     * Return account info
     *
     * @return collection
     */
    private function password()
    {
        return Auth::guard('customer')->user();
    }

    /**
     * Return account info
     *
     * @return collection
     */
    private function account()
    {
        return Auth::guard('customer')->user();
    }

    /**
     * Return delete account page data
     *
     * @return collection
     */
    private function account_delete()
    {
        return Auth::guard('customer')->user();
    }

    /**
     * Return gift_cards
     *
     * @return collection
     */
    private function gift_cards()
    {
        return Auth::guard('customer')->user()->gift_cards()->paginate(20);
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        if (config('app.demo') == true && Auth::guard('customer')->user()->id <= config('system.demo.customers', 1)) {
            return redirect()->route('account', 'account')
                ->with('warning', trans('messages.demo_restriction'));
        }

        $request->validate([
            'email' => 'required|email|max:255|unique:customers,email,'.Auth::guard('customer')->user()->id,
        ]);

        $user = Auth::guard('customer')->user();
        $user->name = $request->input('name');
        $user->nice_name = $request->input('nice_name');
        $user->email = $request->input('email');

        if ($request->has('phone')) {
            $user->phone = $request->input('phone');
        }

        $user->dob = $request->input('dob');
        $user->description = $request->input('description');
        $user->save();

        return redirect()->route('account', 'account')
            ->with('success', trans('theme.notify.info_updated'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function password_update(SelfPasswordUpdateRequest $request)
    {
        if (config('app.demo') == true && Auth::guard('customer')->user()->id <= config('system.demo.customers', 1)) {
            return redirect()->route('account.password')
                ->with('warning', trans('messages.demo_restriction'));
        }

        Auth::guard('customer')->user()->update($request->all());

        // event(new PasswordUpdated(Auth::user()));

        return redirect()->route('account.password')
            ->with('success', trans('theme.notify.info_updated'));
    }

    /**
     * Delivery address picker modal (list saved addresses + add new).
     *
     * @return \Illuminate\Http\Response
     */
    public function select_address(Request $request)
    {
        $customer = Auth::guard('customer')->user();
        $addresses = $customer->addresses()->orderBy('id')->get();
        $buyerLocation = app(BuyerLocationService::class);
        $buyerLocation->ensureDeliveryLocation($customer);

        $activeAddressId = $customer->preferred_address_id;

        if (! $activeAddressId) {
            $sessionLat = session('buyer_latitude');
            $sessionLng = session('buyer_longitude');

            if ($sessionLat !== null && $sessionLng !== null) {
                foreach ($addresses as $address) {
                    if ($address->latitude && $address->longitude
                        && abs((float) $address->latitude - (float) $sessionLat) < 0.0001
                        && abs((float) $address->longitude - (float) $sessionLng) < 0.0001) {
                        $activeAddressId = $address->id;
                        break;
                    }
                }
            }
        }

        return view(
            'theme::modals._select_address',
            compact('addresses', 'activeAddressId')
        )->render();
    }

    /**
     * Apply a saved address as the active delivery location.
     *
     * @return \Illuminate\Http\Response
     */
    public function use_address(Request $request, Address $address)
    {
        $customer = Auth::guard('customer')->user();

        if ($address->addressable_id != $customer->id
            || $address->addressable_type != Customer::class) {
            abort(403);
        }

        $buyerLocation = app(BuyerLocationService::class);

        if (! $buyerLocation->applyAddressAsLocation($address, $customer)) {
            return response()->json([
                'success' => false,
                'message' => trans('theme.address_geocode_failed'),
            ], 422);
        }

        $customer->refresh();

        return response()->json([
            'success' => true,
            'message' => trans('theme.location_saved'),
            'address_text' => $buyerLocation->addressText(),
            'preferred_address_id' => $customer->preferred_address_id,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function create_address(Request $request)
    {
        $countries = ListHelper::countries(); // Country list for ship_to dropdown

        $address_types = ListHelper::address_types();

        $states = config('system_settings.address_default_state') ? ListHelper::states(config('system_settings.address_default_country')) : [];

        return view(
            'theme::modals._create_address',
            compact('countries', 'states', 'address_types')
        )->render();
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function save_address(CreateAddressRequest $request)
    {
        $customer = Auth::guard('customer')->user();
        $address = $customer->addresses()->create($request->all());

        $this->finalizeCustomerAddress($address, $customer);

        return redirect()->route('account.addresses', ['address' => $address->id])
            ->with('success', trans('theme.notify.address_created'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function address_edit(Request $request, Address $address)
    {
        $countries = ListHelper::countries(); // Country list for ship_to dropdown
        $states = $address->state_id ? ListHelper::states($address->country_id) : [];
        $address_types = ListHelper::address_types();

        return view('theme::modals._edit_address', compact('address', 'countries', 'states', 'address_types'))->render();
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function address_update(SelfAddressUpdateRequest $request, Address $address)
    {
        $address->update($request->all());

        $this->finalizeCustomerAddress($address->fresh(), Auth::guard('customer')->user());

        return redirect()->route('account.addresses')
            ->with('success', trans('theme.notify.info_updated'));
    }

    /**
     * delete the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function address_delete(SelfAddressDeleteRequest $request, Address $address)
    {
        $customer = Auth::guard('customer')->user();
        $buyerLocation = app(BuyerLocationService::class);

        if ((int) $customer->preferred_address_id === (int) $address->id) {
            $buyerLocation->clearPreferredAddress($customer);
            session()->forget([
                'buyer_latitude',
                'buyer_longitude',
                'buyer_address_text',
            ]);
        }

        $address->delete();

        $buyerLocation->ensureDeliveryLocation($customer->fresh());

        return redirect()->route('account.addresses')
            ->with('success', trans('theme.notify.address_deleted'));
    }

    public function avatar(SelfAvatarUpdateRequest $request)
    {
        Auth::guard('customer')->user()->deleteImage();

        Auth::guard('customer')->user()->saveImage($request->file('avatar'));

        return redirect()->route('account', 'account')
            ->with('success', trans('theme.notify.info_updated'));
    }

    public function delete_avatar(Request $request)
    {
        Auth::guard('customer')->user()->deleteImage();

        return redirect()->route('account', 'account')
            ->with('success', trans('theme.notify.info_deleted'));
    }

    /**
     * Delete customer own account
     */
    public function delete_account()
    {
        $customer = Auth::guard('customer')->user();

        $customer->flushAddresses();

        $customer->flushImages();

        $customer->forceDelete();

        return redirect()->route('homepage')->with('success', trans('theme.notify.account_delete'));
    }

    /**
     * log in to merchant account from customer
     *
     * @param  $id
     */
    public function switchToMerchant()
    {
        $user = Auth::guard('customer')->user();
        $merchant = Merchant::where('email', $user->email)->first();

        if (! $merchant) {
            return redirect()->back()->with('error', trans('theme.notify.merchant_acc_not_exist'));
        }

        try {
            Auth::guard('customer')->logout();                  // Logout the customer
            Auth::guard('web')->loginUsingId($merchant->id);    // Log in as the merchant
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('merchant.dashboard')
            ->with('success', trans('theme.notify.switched_to_merchant_successfully'));
    }

    /**
     * Ensure map coordinates are stored and sync buyer delivery location.
     */
    protected function finalizeCustomerAddress(Address $address, Customer $customer): void
    {
        if (! $address->latitude || ! $address->longitude) {
            app(GeocodeService::class)->applyToAddress($address->fresh());
            $address->refresh();
        }

        $buyerLocation = app(BuyerLocationService::class);

        if ($address->address_type === 'Primary' || ! $buyerLocation->hasLocation()) {
            $buyerLocation->applyAddressAsLocation($address, $customer);
        }
    }
}
