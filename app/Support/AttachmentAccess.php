<?php

namespace App\Support;

use App\Models\Attachment;
use App\Models\Config;
use App\Models\Customer;
use App\Models\Feedback;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Reply;
use App\Models\Review;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Who may read an uploaded attachment: access follows the record it is attached to.
 */
class AttachmentAccess
{
    public static function allows(Attachment $attachment): bool
    {
        $record = $attachment->attachable;

        // A reply's files belong to its thread (message, ticket, dispute, ...).
        if ($record instanceof Reply) {
            $record = $record->repliable;
        }

        if (! $record) {
            return false;
        }

        // Review and feedback photos are shown publicly on product and shop pages.
        if ($record instanceof Review || $record instanceof Feedback) {
            return true;
        }

        $user = Auth::guard('web')->user() ?? Auth::guard('vendor_api')->user();
        if ($user instanceof User && self::userMayRead($user, $record)) {
            return true;
        }

        $customer = Auth::guard('customer')->user() ?? Auth::guard('api')->user();
        if ($customer instanceof Customer && self::customerMayRead($customer, $record)) {
            return true;
        }

        return false;
    }

    private static function userMayRead(User $user, $record): bool
    {
        if ($user->isFromPlatform()) {
            return true;
        }

        $shopId = (int) $user->merchantId();

        if ($record instanceof User) {
            return (int) $record->id === (int) $user->id;
        }

        if ($record instanceof Shop) {
            return $shopId > 0 && (int) $record->id === $shopId;
        }

        return $shopId > 0 && isset($record->shop_id) && (int) $record->shop_id === $shopId;
    }

    private static function customerMayRead(Customer $customer, $record): bool
    {
        if ($record instanceof Customer) {
            return (int) $record->id === (int) $customer->id;
        }

        // Digital goods: only for a paid order of this customer that contains the item.
        if ($record instanceof Inventory) {
            return Order::paid()
                ->where('customer_id', $customer->id)
                ->whereHas('inventories', fn ($q) => $q->where('inventories.id', $record->id))
                ->exists();
        }

        if ($record instanceof Config) {
            return false;
        }

        return isset($record->customer_id) && (int) $record->customer_id === (int) $customer->id;
    }
}
