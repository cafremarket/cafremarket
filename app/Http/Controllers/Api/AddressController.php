<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ListHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Validations\CreateAddressRequest;
use App\Http\Requests\Validations\SelfAddressDeleteRequest;
use App\Http\Requests\Validations\SelfAddressUpdateRequest;
use App\Http\Resources\AddressResource;
use App\Models\Address;
use App\Models\Customer;
use App\Services\Geo\GeocodeService;
use App\Services\Hyperlocal\BuyerLocationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AddressController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // $addresses = Auth::guard('api')->user()->addresses()->create($request->all());

        return AddressResource::collection(Auth::guard('api')->user()->addresses);
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        return [
            'address_types' => ListHelper::address_types(),
            'countries' => ListHelper::countries(),
        ];
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CreateAddressRequest $request)
    {
        $customer = Auth::guard('api')->user();
        $address = $customer->addresses()->create($request->all());
        $this->finalizeCustomerAddress($address, $customer);

        return AddressResource::collection($customer->fresh()->addresses);
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(Address $address)
    {
        return (new AddressResource($address))->additional([
            'address_types' => ListHelper::address_types(),
            'countries' => ListHelper::countries(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(SelfAddressUpdateRequest $request, Address $address)
    {
        $address->update($request->all());
        $this->finalizeCustomerAddress($address->fresh(), Auth::guard('api')->user());

        return AddressResource::collection(Auth::guard('api')->user()->addresses);
    }

    /**
     * delete the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function delete(SelfAddressDeleteRequest $request, Address $address)
    {
        $address->delete();

        return AddressResource::collection(Auth::guard('api')->user()->addresses);
    }

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
