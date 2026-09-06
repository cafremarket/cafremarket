<?php

namespace App\Http\Controllers;

use App\Helpers\ListHelper;
use App\Http\Requests\Validations\CreateAddressRequest;
use App\Http\Requests\Validations\UpdateAddressRequest;
use App\Models\Shop;
use App\Repositories\Address\AddressRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AddressController extends Controller
{
    private $model_name;

    private $address;

    /**
     * construct
     */
    public function __construct(AddressRepository $address)
    {
        parent::__construct();

        $this->model_name = trans('app.model.address');
        $this->address = $address;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function addresses($addressable_type, $addressable_id)
    {
        $data = $this->address->addresses($addressable_type, $addressable_id);

        return view('address.show', $data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($addressable_type, $addressable_id)
    {
        if ($response = $this->denyPlatformShopAddressMutation($addressable_type)) {
            return $response;
        }

        $addressable_type = get_qualified_model($addressable_type);

        return view('address._create', compact(['addressable_type', 'addressable_id']));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CreateAddressRequest $request)
    {
        $addressableType = $request->input('addressable_type');
        if ($response = $this->denyPlatformShopAddressMutation($addressableType)) {
            return $response;
        }

        $this->address->store($request);

        return back()->with('success', trans('messages.created', ['model' => $this->model_name]));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $address = $this->address->find($id);

        if ($response = $this->denyPlatformShopAddressMutation(null, $address)) {
            return $response;
        }

        return view('address._edit', compact('address'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateAddressRequest $request, $id)
    {
        $address = $this->address->find($id);

        if ($response = $this->denyPlatformShopAddressMutation(null, $address)) {
            return $response;
        }

        $this->address->update($request, $id);

        return back()->with('success', trans('messages.updated', ['model' => $this->model_name]));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        $address = $this->address->find($id);

        if ($response = $this->denyPlatformShopAddressMutation(null, $address)) {
            return $response;
        }

        $this->address->destroy($id);

        return back()->with('success', trans('messages.deleted', ['model' => $this->model_name]));
    }

    /**
     * Response AJAX call to return states of a give country
     */
    public function ajaxCountryStates(Request $request)
    {
        if ($request->ajax()) {
            $states = ListHelper::states($request->input('id'));

            return response($states, 200);
        }

        return response('Not allowed!', 404);
    }

    /**
     * Platform admins must approve store address-change requests — they cannot
     * create/edit shop addresses directly.
     *
     * @param  string|null  $addressableType
     * @param  mixed  $address
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|null
     */
    private function denyPlatformShopAddressMutation($addressableType = null, $address = null)
    {
        $user = Auth::user();
        if (! $user || ! method_exists($user, 'isFromPlatform') || ! $user->isFromPlatform()) {
            return null;
        }

        $isShopAddress = false;

        if ($address && isset($address->addressable_type)) {
            $isShopAddress = $this->isShopAddressableType($address->addressable_type);
        }

        if ($addressableType) {
            $isShopAddress = $this->isShopAddressableType($addressableType)
                || $this->isShopAddressableType(get_qualified_model($addressableType));
        }

        if (! $isShopAddress) {
            return null;
        }

        $message = trans('messages.admin_cannot_update_shop_address');

        if (request()->ajax() || request()->wantsJson()) {
            return response(
                '<div class="modal-body"><div class="alert alert-warning" style="margin:0;">'
                .e($message).
                '</div></div>',
                403
            );
        }

        return back()->with('error', $message);
    }

    private function isShopAddressableType($type): bool
    {
        if (! is_string($type) || $type === '') {
            return false;
        }

        return $type === Shop::class
            || $type === 'shop'
            || $type === 'shops'
            || str_ends_with($type, '\\Shop');
    }
}
