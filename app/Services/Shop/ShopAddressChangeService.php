<?php

namespace App\Services\Shop;

use App\Models\Address;
use App\Models\Shop;
use App\Models\ShopAddressChangeRequest;
use App\Models\User;
use App\Services\Geo\GeocodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ShopAddressChangeService
{
    public function requiresApproval(Shop $shop, ?Address $address): bool
    {
        if (! $address || ! $address->latitude || ! $address->longitude) {
            return false;
        }

        if (! $shop->address_verified && ! $shop->active) {
            return false;
        }

        return true;
    }

    public function hasPendingRequest(int $shopId): bool
    {
        return ShopAddressChangeRequest::query()
            ->where('shop_id', $shopId)
            ->where('status', ShopAddressChangeRequest::STATUS_PENDING)
            ->exists();
    }

    public function pendingForShop(int $shopId): ?ShopAddressChangeRequest
    {
        return ShopAddressChangeRequest::query()
            ->where('shop_id', $shopId)
            ->where('status', ShopAddressChangeRequest::STATUS_PENDING)
            ->latest()
            ->first();
    }

    public function submitRequest(Shop $shop, Address $currentAddress, Request $request, ?User $user = null): ShopAddressChangeRequest
    {
        if ($this->hasPendingRequest($shop->id)) {
            throw new \RuntimeException(trans('messages.address_change_request_pending'));
        }

        $requested = $this->snapshotFromRequest($request);

        return ShopAddressChangeRequest::create([
            'shop_id' => $shop->id,
            'address_id' => $currentAddress->id,
            'requested_by' => $user?->id ?? Auth::id(),
            'previous_address' => $this->snapshotAddress($currentAddress),
            'requested_address' => $requested,
            'status' => ShopAddressChangeRequest::STATUS_PENDING,
        ]);
    }

    public function approve(ShopAddressChangeRequest $changeRequest, ?User $reviewer = null): void
    {
        if (! $changeRequest->isPending()) {
            throw new \RuntimeException(trans('messages.address_change_request_not_pending'));
        }

        $shop = $changeRequest->shop;
        $data = $changeRequest->requested_address ?? [];
        $address = $shop->storeAddress();

        if (! $address) {
            $address = $shop->addresses()->create([
                'address_type' => 'Primary',
                ...$this->addressAttributesFromSnapshot($data),
            ]);
        } else {
            $address->fill($this->addressAttributesFromSnapshot($data));
            $address->save();
        }

        if (! $address->latitude || ! $address->longitude) {
            app(GeocodeService::class)->applyToAddress($address->fresh());
            $address->refresh();
        }

        $shop->update(['primary_address_id' => $address->id]);

        $changeRequest->update([
            'status' => ShopAddressChangeRequest::STATUS_APPROVED,
            'reviewed_by' => $reviewer?->id ?? Auth::id(),
            'reviewed_at' => now(),
            'rejection_reason' => null,
        ]);

        clearShopConfigCache($shop->id);
    }

    public function reject(ShopAddressChangeRequest $changeRequest, string $reason, ?User $reviewer = null): void
    {
        if (! $changeRequest->isPending()) {
            throw new \RuntimeException(trans('messages.address_change_request_not_pending'));
        }

        $changeRequest->update([
            'status' => ShopAddressChangeRequest::STATUS_REJECTED,
            'rejection_reason' => $reason,
            'reviewed_by' => $reviewer?->id ?? Auth::id(),
            'reviewed_at' => now(),
        ]);
    }

    public function snapshotAddress(Address $address): array
    {
        return $address->only([
            'address_title',
            'address_line_1',
            'address_line_2',
            'landmark',
            'city',
            'zip_code',
            'country_id',
            'state_id',
            'phone',
            'latitude',
            'longitude',
        ]);
    }

    public function snapshotFromRequest(Request $request): array
    {
        return [
            'address_title' => $request->input('address_title'),
            'address_line_1' => $request->input('address_line_1'),
            'address_line_2' => $request->input('address_line_2'),
            'landmark' => $request->input('landmark'),
            'city' => $request->input('city'),
            'zip_code' => $request->input('zip_code'),
            'country_id' => $request->input('country_id'),
            'state_id' => $request->input('state_id'),
            'phone' => $request->input('phone'),
            'latitude' => $request->input('latitude'),
            'longitude' => $request->input('longitude'),
        ];
    }

    protected function addressAttributesFromSnapshot(array $data): array
    {
        return [
            'address_title' => $data['address_title'] ?? null,
            'address_line_1' => $data['address_line_1'] ?? '',
            'address_line_2' => $data['address_line_2'] ?? null,
            'landmark' => $data['landmark'] ?? null,
            'city' => $data['city'] ?? '',
            'zip_code' => $data['zip_code'] ?? '00000',
            'country_id' => $data['country_id'] ?? config('system_settings.address_default_country'),
            'state_id' => $data['state_id'] ?? config('system_settings.address_default_state'),
            'phone' => $data['phone'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
        ];
    }
}
