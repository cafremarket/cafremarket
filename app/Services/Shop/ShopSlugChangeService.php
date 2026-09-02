<?php

namespace App\Services\Shop;

use App\Models\Shop;
use App\Models\ShopSlugChangeRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ShopSlugChangeService
{
    public function requiresApproval(Shop $shop, ?string $newSlug): bool
    {
        if (! $newSlug || $newSlug === $shop->slug) {
            return false;
        }

        if (! $shop->isVerified() && ! $shop->active) {
            return false;
        }

        return true;
    }

    public function hasPendingRequest(int $shopId): bool
    {
        return ShopSlugChangeRequest::query()
            ->where('shop_id', $shopId)
            ->where('status', ShopSlugChangeRequest::STATUS_PENDING)
            ->exists();
    }

    public function pendingForShop(int $shopId): ?ShopSlugChangeRequest
    {
        return ShopSlugChangeRequest::query()
            ->where('shop_id', $shopId)
            ->where('status', ShopSlugChangeRequest::STATUS_PENDING)
            ->latest()
            ->first();
    }

    public function submitRequest(Shop $shop, string $requestedSlug, ?User $user = null): ShopSlugChangeRequest
    {
        if ($this->hasPendingRequest($shop->id)) {
            throw new \RuntimeException(trans('messages.slug_change_request_pending'));
        }

        if (Shop::query()->where('slug', $requestedSlug)->where('id', '!=', $shop->id)->exists()) {
            throw ValidationException::withMessages([
                'slug' => trans('validation.unique', ['attribute' => trans('app.slug')]),
            ]);
        }

        return ShopSlugChangeRequest::create([
            'shop_id' => $shop->id,
            'requested_by' => $user?->id ?? Auth::id(),
            'previous_slug' => $shop->slug,
            'requested_slug' => $requestedSlug,
            'status' => ShopSlugChangeRequest::STATUS_PENDING,
        ]);
    }

    public function approve(ShopSlugChangeRequest $changeRequest, ?User $reviewer = null): void
    {
        if (! $changeRequest->isPending()) {
            throw new \RuntimeException(trans('messages.slug_change_request_not_pending'));
        }

        $shop = $changeRequest->shop;

        if (Shop::query()->where('slug', $changeRequest->requested_slug)->where('id', '!=', $shop->id)->exists()) {
            throw new \RuntimeException(trans('messages.slug_change_request_slug_taken'));
        }

        $shop->update(['slug' => $changeRequest->requested_slug]);

        $changeRequest->update([
            'status' => ShopSlugChangeRequest::STATUS_APPROVED,
            'reviewed_by' => $reviewer?->id ?? Auth::id(),
            'reviewed_at' => now(),
            'rejection_reason' => null,
        ]);

        clearShopConfigCache($shop->id);
    }

    public function reject(ShopSlugChangeRequest $changeRequest, string $reason, ?User $reviewer = null): void
    {
        if (! $changeRequest->isPending()) {
            throw new \RuntimeException(trans('messages.slug_change_request_not_pending'));
        }

        $changeRequest->update([
            'status' => ShopSlugChangeRequest::STATUS_REJECTED,
            'rejection_reason' => $reason,
            'reviewed_by' => $reviewer?->id ?? Auth::id(),
            'reviewed_at' => now(),
        ]);
    }
}
