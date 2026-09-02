<?php

namespace App\Services\Hyperlocal;

use App\Models\Address;
use App\Models\Customer;
use App\Services\Geo\GeocodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BuyerLocationService
{
    public function hasLocation(): bool
    {
        return $this->latitude() !== null && $this->longitude() !== null;
    }

    public function latitude(): ?float
    {
        $fromRequest = $this->coordinatesFromRequest();

        if ($fromRequest['lat'] !== null) {
            return $fromRequest['lat'];
        }

        $lat = session('buyer_latitude');

        if ($lat !== null && $lat !== '') {
            return (float) $lat;
        }

        $customer = $this->customer();

        if ($customer && $customer->preferred_latitude) {
            return (float) $customer->preferred_latitude;
        }

        return null;
    }

    public function longitude(): ?float
    {
        $fromRequest = $this->coordinatesFromRequest();

        if ($fromRequest['lng'] !== null) {
            return $fromRequest['lng'];
        }

        $lng = session('buyer_longitude');

        if ($lng !== null && $lng !== '') {
            return (float) $lng;
        }

        $customer = $this->customer();

        if ($customer && $customer->preferred_longitude) {
            return (float) $customer->preferred_longitude;
        }

        return null;
    }

    public function addressText(): ?string
    {
        if (session('buyer_address_text')) {
            return session('buyer_address_text');
        }

        return $this->customer()?->preferred_address_text;
    }

    public function save(float $latitude, float $longitude, ?string $addressText = null, ?Request $request = null): void
    {
        session([
            'buyer_latitude' => $latitude,
            'buyer_longitude' => $longitude,
            'buyer_address_text' => $addressText,
        ]);

        $customer = $this->customer($request);

        if ($customer) {
            $customer->update([
                'preferred_latitude' => $latitude,
                'preferred_longitude' => $longitude,
                'preferred_address_text' => $addressText,
            ]);
        }
    }

    /**
     * Resolve the customer's default delivery address (Primary, then Shipping, then any geocoded).
     */
    public function defaultDeliveryAddress(?Customer $customer = null): ?Address
    {
        $customer = $customer ?? $this->customer();

        if (! $customer) {
            return null;
        }

        if ($primary = $customer->primaryAddress) {
            return $primary;
        }

        if ($shipping = $customer->shippingAddress) {
            return $shipping;
        }

        $geocoded = $customer->addresses()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->orderBy('id')
            ->first();

        if ($geocoded) {
            return $geocoded;
        }

        return $customer->addresses()->orderBy('id')->first();
    }

    /**
     * Use a saved address as the active buyer delivery location.
     */
    public function applyAddressAsLocation(Address $address, ?Customer $customer = null): bool
    {
        $lat = $address->latitude;
        $lng = $address->longitude;

        if (! $lat || ! $lng) {
            $geocoder = app(GeocodeService::class);
            $coords = $geocoder->geocode($address);

            if (empty($coords)) {
                return false;
            }

            $geocoder->applyToAddress($address);
            $address->refresh();

            $lat = $address->latitude;
            $lng = $address->longitude;
        }

        if (! $lat || ! $lng) {
            return false;
        }

        $addressText = $address->toString(true) ?: $address->toShortString();

        $this->save((float) $lat, (float) $lng, $addressText ?: null);

        return true;
    }

    /**
     * Sync buyer location from customer profile or saved default address.
     */
    public function syncFromSavedAddress(?Customer $customer = null): bool
    {
        if ($this->hasLocation()) {
            return true;
        }

        $customer = $customer ?? $this->customer();

        if (! $customer) {
            return false;
        }

        if ($customer->preferred_latitude && $customer->preferred_longitude) {
            session([
                'buyer_latitude' => (float) $customer->preferred_latitude,
                'buyer_longitude' => (float) $customer->preferred_longitude,
                'buyer_address_text' => $customer->preferred_address_text,
            ]);

            return true;
        }

        $address = $this->defaultDeliveryAddress($customer);

        if (! $address) {
            return false;
        }

        return $this->applyAddressAsLocation($address, $customer);
    }

    public function syncFromCustomer(): void
    {
        $this->syncFromSavedAddress();
    }

    public function toArray(): array
    {
        return [
            'latitude' => $this->latitude(),
            'longitude' => $this->longitude(),
            'address_text' => $this->addressText(),
            'has_location' => $this->hasLocation(),
        ];
    }

    protected function customer(?Request $request = null): ?Customer
    {
        if ($request) {
            if ($customer = Auth::guard('customer')->user()) {
                return $customer;
            }

            if ($customer = Auth::guard('api')->user()) {
                return $customer;
            }
        }

        return Auth::guard('customer')->user() ?? Auth::guard('api')->user();
    }

    /**
     * Mobile/API clients send coordinates via query or headers on each request.
     *
     * @return array{lat: ?float, lng: ?float}
     */
    protected function coordinatesFromRequest(): array
    {
        $request = request();

        if (! $request) {
            return ['lat' => null, 'lng' => null];
        }

        $lat = $request->get('lat', $request->header('X-Buyer-Latitude'));
        $lng = $request->get('lng', $request->header('X-Buyer-Longitude'));

        if ($lat === null || $lng === null || $lat === '' || $lng === '') {
            return ['lat' => null, 'lng' => null];
        }

        return ['lat' => (float) $lat, 'lng' => (float) $lng];
    }
}
