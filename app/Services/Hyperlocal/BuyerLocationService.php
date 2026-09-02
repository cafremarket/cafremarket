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

    public function activeAddressId(?Customer $customer = null): ?int
    {
        $customer = $customer ?? $this->customer();

        return $customer?->preferred_address_id;
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

    /**
     * Keep session, customer profile, and saved addresses aligned.
     */
    public function ensureDeliveryLocation(?Customer $customer = null): bool
    {
        $customer = $customer ?? $this->customer();

        if ($customer && $this->restorePreferredAddress($customer)) {
            return true;
        }

        if ($this->hasLocation()) {
            $this->hydrateSessionFromResolvedLocation($customer);

            return true;
        }

        if (session()->has('buyer_address_text')) {
            session()->forget([
                'buyer_latitude',
                'buyer_longitude',
                'buyer_address_text',
            ]);
        }

        if (! $customer) {
            return false;
        }

        return $this->syncFromSavedAddress($customer);
    }

    public function save(
        float $latitude,
        float $longitude,
        ?string $addressText = null,
        ?Request $request = null,
        ?int $addressId = null
    ): void {
        $customer = $this->customer($request);
        $resolvedAddressText = $addressText
            ?? session('buyer_address_text')
            ?? $customer?->preferred_address_text;

        session([
            'buyer_latitude' => $latitude,
            'buyer_longitude' => $longitude,
            'buyer_address_text' => $resolvedAddressText,
        ]);

        if ($customer) {
            $payload = [
                'preferred_latitude' => $latitude,
                'preferred_longitude' => $longitude,
                'preferred_address_text' => $resolvedAddressText,
            ];

            if ($addressId !== null) {
                $payload['preferred_address_id'] = $addressId;
            }

            $customer->update($payload);
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
        $customer = $customer ?? $this->customer();

        if ($customer
            && ($address->addressable_id != $customer->id
                || $address->addressable_type != Customer::class)) {
            return false;
        }

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

        $this->save((float) $lat, (float) $lng, $addressText ?: null, null, (int) $address->id);

        return true;
    }

    /**
     * Sync buyer location from customer profile or saved default address.
     */
    public function syncFromSavedAddress(?Customer $customer = null): bool
    {
        $customer = $customer ?? $this->customer();

        if (! $customer) {
            return $this->hasLocation();
        }

        if ($this->restorePreferredAddress($customer)) {
            return true;
        }

        if ($customer->preferred_latitude && $customer->preferred_longitude) {
            $this->save(
                (float) $customer->preferred_latitude,
                (float) $customer->preferred_longitude,
                $customer->preferred_address_text
            );

            return true;
        }

        $address = $this->defaultDeliveryAddress($customer);

        if (! $address) {
            return false;
        }

        if (! $address->latitude || ! $address->longitude) {
            app(GeocodeService::class)->applyToAddress($address);
            $address->refresh();
        }

        return $this->applyAddressAsLocation($address, $customer);
    }

    public function syncFromCustomer(): void
    {
        $this->ensureDeliveryLocation();
    }

    public function clearPreferredAddress(?Customer $customer = null): void
    {
        $customer = $customer ?? $this->customer();

        if ($customer) {
            $customer->update(['preferred_address_id' => null]);
        }
    }

    public function toArray(): array
    {
        return [
            'latitude' => $this->latitude(),
            'longitude' => $this->longitude(),
            'address_text' => $this->addressText(),
            'has_location' => $this->hasLocation(),
            'preferred_address_id' => $this->activeAddressId(),
        ];
    }

    protected function restorePreferredAddress(Customer $customer): bool
    {
        if (! $customer->preferred_address_id) {
            return false;
        }

        $address = Address::query()
            ->where('id', $customer->preferred_address_id)
            ->where('addressable_id', $customer->id)
            ->where('addressable_type', Customer::class)
            ->first();

        if (! $address) {
            $customer->update(['preferred_address_id' => null]);

            return false;
        }

        $lat = $address->latitude;
        $lng = $address->longitude;

        if (! $lat || ! $lng) {
            return $this->applyAddressAsLocation($address, $customer);
        }

        $addressText = $address->toString(true) ?: $address->toShortString();

        session([
            'buyer_latitude' => (float) $lat,
            'buyer_longitude' => (float) $lng,
            'buyer_address_text' => $addressText,
        ]);

        if (
            (float) $customer->preferred_latitude !== (float) $lat
            || (float) $customer->preferred_longitude !== (float) $lng
            || $customer->preferred_address_text !== $addressText
        ) {
            $customer->update([
                'preferred_latitude' => $lat,
                'preferred_longitude' => $lng,
                'preferred_address_text' => $addressText,
                'preferred_address_id' => $address->id,
            ]);
        }

        return true;
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

    protected function hydrateSessionFromResolvedLocation(?Customer $customer = null): void
    {
        if (! $this->hasLocation()) {
            return;
        }

        session([
            'buyer_latitude' => $this->latitude(),
            'buyer_longitude' => $this->longitude(),
            'buyer_address_text' => $this->addressText(),
        ]);
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

        $customer = $this->customer();

        // Logged-in customers with a chosen saved address always use that — not URL params.
        if ($customer && $customer->preferred_address_id) {
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
