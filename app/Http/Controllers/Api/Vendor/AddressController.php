<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Validations\CreateAddressRequest;
use App\Http\Resources\AddressResource;
use App\Models\Address;
use App\Models\User;
use App\Services\Geo\GeocodeService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AddressController extends Controller
{
    /**
     * Search for an address/place by free-text query, for the store-location
     * map picker's search box. Delegates to GeocodeService, which uses the
     * Google Maps key configured in the web .env when present, falling back
     * to free OpenStreetMap/Nominatim otherwise.
     *
     * @return \Illuminate\Http\Response
     */
    public function searchLocation(Request $request, GeocodeService $geocoder)
    {
        $request->validate([
            'query' => 'required|string|min:3|max:200',
        ]);

        return response()->json([
            'results' => $geocoder->searchAddresses($request->query('query')),
        ]);
    }

    /**
     * Reverse-geocode a picked map coordinate into a human-readable address
     * (and structured components) for the store-location map picker.
     *
     * @return \Illuminate\Http\Response
     */
    public function reverseGeocode(Request $request, GeocodeService $geocoder)
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        return response()->json([
            'address_text' => $geocoder->reverseGeocode(
                (float) $request->latitude,
                (float) $request->longitude
            ),
            'details' => $geocoder->reverseGeocodeDetails(
                (float) $request->latitude,
                (float) $request->longitude
            ),
        ]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $vendor = Auth::guard('vendor_api')->user();
        $user = $vendor;

        // Another user's addresses only for a colleague in the same shop.
        if ($request->has('user_id')) {
            $user = User::where('id', $request->get('user_id'))
                ->where('shop_id', $vendor->merchantId())
                ->firstOrFail();
        }

        $addresses = $user->addresses()->with('country', 'state')->get();

        return AddressResource::collection($addresses);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CreateAddressRequest $request)
    {
        try {
            Auth::guard('vendor_api')->user()->addresses()->create($request->only(Address::SELF_EDITABLE));
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }

        return response()->json(['message' => trans('api.address_created_successfully')], 200);
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Address $address)
    {
        $this->assertOwnsAddress($address);

        return new AddressResource($address);
    }

    /**
     * Update user profile
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Address $address)
    {
        $this->assertOwnsAddress($address);

        try {
            $address->update($request->only(\App\Models\Address::SELF_EDITABLE));
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }

        return response()->json(['message' => trans('api.address_updated_successfully')], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Address $address_id)
    {
        //
    }

    /**
     * The vendor's own address, or an address of their shop.
     */
    private function assertOwnsAddress(Address $address): void
    {
        $vendor = Auth::guard('vendor_api')->user();

        $ownsIt = ($address->addressable_type === User::class && (int) $address->addressable_id === (int) $vendor->id)
            || ($address->addressable_type === \App\Models\Shop::class && (int) $address->addressable_id === (int) $vendor->merchantId());

        abort_unless($ownsIt, 403);
    }
}
