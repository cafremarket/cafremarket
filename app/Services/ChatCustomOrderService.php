<?php

namespace App\Services;

use App\Models\Address;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Shop;
use App\Models\Tax;
use App\Models\User;
use App\Repositories\Order\OrderRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Incevio\Package\LiveChat\Models\ChatConversation;

/**
 * Lets a vendor build and share a custom-priced order (not tied to an
 * existing catalog listing) from inside a chat thread. Every custom line
 * item is backed by a real, hidden (`active = false`) Product + Inventory
 * row so the resulting Order is a normal Order under the hood — it flows
 * through the existing order-creation, stock, invoice, and order-share
 * chat card code unmodified.
 */
class ChatCustomOrderService
{
    /**
     * Saved addresses of the chat's customer, formatted for the address
     * selector on the "Create custom order" form (web modal + vendor app).
     *
     * @return array{data: array<int, array>, default_shipping_address_id: int|null, default_billing_address_id: int|null}
     */
    public function customerAddresses(ChatConversation $chat): array
    {
        $customer = $chat->customer;

        if (! $customer) {
            return ['data' => [], 'default_shipping_address_id' => null, 'default_billing_address_id' => null];
        }

        $addresses = $customer->addresses()->get();
        $defaultShipping = $customer->shippingAddress ?? $customer->primaryAddress ?? $addresses->first();
        $defaultBilling = $customer->billingAddress ?? $defaultShipping;

        return [
            'data' => $addresses->map(function (Address $address) use ($chat) {
                $zone = get_shipping_zone_of($chat->shop_id, $address->country_id, $address->state_id);

                return [
                    'id' => $address->id,
                    'address_type' => $address->address_type,
                    'address_title' => $address->address_title,
                    'address_line_1' => $address->address_line_1,
                    'address_line_2' => $address->address_line_2,
                    'landmark' => $address->landmark,
                    'city' => $address->city,
                    'state' => optional($address->state)->name,
                    'zip_code' => $address->zip_code,
                    'country' => optional($address->country)->name,
                    'phone' => $address->phone,
                    'formatted' => $address->toString(),
                    'shipping_zone' => isset($zone->id) ? ['id' => $zone->id, 'name' => $zone->name] : null,
                ];
            })->values()->all(),
            'default_shipping_address_id' => optional($defaultShipping)->id,
            'default_billing_address_id' => optional($defaultBilling)->id,
        ];
    }

    /**
     * The shop's own configured Tax/Shipping defaults used to pre-fill the
     * "Create custom order" form instead of starting every quote at 0.
     *
     * @return array{shipping: float, tax: float, tax_type: string}
     */
    public function orderDefaults(ChatConversation $chat): array
    {
        $defaults = ['shipping' => 0.0, 'tax' => 0.0, 'tax_type' => 'amount'];
        $shop = $chat->shop;

        if (! $shop) {
            return $defaults;
        }

        $defaultTax = $shop->taxes()->where('active', true)->orderBy('id')->first();
        if ($defaultTax) {
            $defaults['tax'] = (float) $defaultTax->taxrate;
            $defaults['tax_type'] = $defaultTax->type === Tax::TYPE_PERCENT ? 'percent' : 'amount';
        }

        $defaultZone = ShippingZone::where('shop_id', $shop->id)->where('active', true)->orderBy('id')->first();
        if ($defaultZone) {
            $defaultRate = ShippingRate::where('shipping_zone_id', $defaultZone->id)->orderBy('rate')->first();
            if ($defaultRate) {
                $defaults['shipping'] = (float) $defaultRate->rate;
            }
        }

        return $defaults;
    }

    /**
     * Find an address that belongs to the chat's customer.
     */
    protected function customerAddress(ChatConversation $chat, $addressId): ?Address
    {
        if (! $addressId || ! $chat->customer_id) {
            return null;
        }

        $address = Address::where('id', $addressId)
            ->where('addressable_type', Customer::class)
            ->where('addressable_id', $chat->customer_id)
            ->first();

        if (! $address) {
            throw new \InvalidArgumentException(trans('validation.exists', ['attribute' => 'address']));
        }

        return $address;
    }

    /**
     * Create a hidden catalog item to back one custom line item.
     */
    public function createLineItem(Shop $shop, string $title, float $price): Inventory
    {
        $product = Product::create([
            'shop_id' => $shop->id,
            'name' => $title,
            'slug' => Str::slug($title).'-'.Str::random(6),
            'active' => false,
            'is_chat_custom' => true,
        ]);

        return Inventory::create([
            'shop_id' => $shop->id,
            'product_id' => $product->id,
            'title' => $title,
            'sku' => 'CHAT-'.Str::upper(Str::random(8)),
            'condition' => 'New',
            'sale_price' => $price,
            'slug' => (string) Str::uuid(),
            // Stock math (StockService::sell()) runs on every order line item —
            // seed high so a chat-custom item never blocks/goes negative.
            'stock_quantity' => 999999,
            'min_order_quantity' => 1,
            'active' => false,
            'is_chat_custom' => true,
        ]);
    }

    /**
     * Build the order-creation `cart` entry for one requested line item,
     * resolving an existing catalog inventory when the vendor picked one,
     * else provisioning a hidden one via createLineItem().
     *
     * @param  array{title?: string|null, quantity: int, unit_price: float, inventory_id?: int|null}  $item
     * @return array{inventory_id: int, item_description: string, quantity: int, unit_price: float}
     */
    protected function resolveCartLine(Shop $shop, array $item): array
    {
        $quantity = (int) ($item['quantity'] ?? 1);
        $unitPrice = (float) ($item['unit_price'] ?? 0);
        $title = trim((string) ($item['title'] ?? ''));
        $inventoryId = $item['inventory_id'] ?? null;

        if ($inventoryId) {
            $inventory = Inventory::where('id', $inventoryId)
                ->where('shop_id', $shop->id)
                ->first();

            if ($inventory) {
                return [
                    'inventory_id' => $inventory->id,
                    'item_description' => $title !== '' ? $title : $inventory->title,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    // setAdditionalCartInfo() reads this key unconditionally — a
                    // real catalog item has a real weight; a hidden custom item has none.
                    'shipping_weight' => $inventory->shipping_weight,
                ];
            }
        }

        $inventory = $this->createLineItem($shop, $title !== '' ? $title : 'Custom item', $unitPrice);

        return [
            'inventory_id' => $inventory->id,
            'item_description' => $inventory->title,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'shipping_weight' => null,
        ];
    }

    /**
     * Create a real Order from vendor-entered chat line items/totals, then
     * post it into the chat thread via the existing order_share mechanism.
     *
     * @param  array<int, array{title?: string|null, quantity: int, unit_price: float, inventory_id?: int|null}>  $items
     * @param  array{shipping_cost?: float|null, tax?: float|null, discount?: float|null, payment_method_id?: int|null, billing_address?: string|null}  $totals
     */
    public function createOrderFromChat(
        ChatConversation $chat,
        Request $request,
        User $vendorUser,
        array $items,
        array $totals
    ): Order {
        $shop = $chat->shop;

        $cart = array_map(
            fn (array $item) => $this->resolveCartLine($shop, $item),
            $items
        );

        // The seller no longer picks a payment method — the customer chooses
        // their own and pays via the existing change-payment-method flow
        // (Api\OrderController::changePaymentMethod). The orders table still
        // requires *a* value, so fall back to the shop's own first enabled
        // method purely as a technical placeholder until the customer pays.
        $paymentMethodId = $totals['payment_method_id']
            ?? optional($shop->paymentMethods()->where('enabled', true)->orderBy('order')->first())->id
            ?? 1;

        // Seller-selected customer addresses (from the address selector).
        // The Order mutators turn an address ID into its formatted string.
        $shippingAddress = $this->customerAddress($chat, $totals['shipping_address_id'] ?? null);
        $billingAddress = $this->customerAddress($chat, $totals['billing_address_id'] ?? null) ?? $shippingAddress;
        $addressFields = [];

        if ($shippingAddress) {
            $zone = get_shipping_zone_of($shop->id, $shippingAddress->country_id, $shippingAddress->state_id);

            $addressFields = [
                'ship_to' => $shippingAddress->id,
                'shipping_address' => $shippingAddress->id,
                'shipping_zone_id' => $zone->id ?? null,
            ];
        }

        // Reuse the exact request the controller received — it is already
        // authenticated as this vendor (auth:vendor_api sets the default
        // guard for $request->user()), so merging our fields onto it and
        // handing it to the existing OrderRepository::store() runs the
        // same validation-free path Api\Vendor\OrderController@store uses,
        // with no forked order-creation logic.
        $request->merge([
            'order_number' => get_formated_order_number($shop->id),
            'shop_id' => $shop->id,
            'customer_id' => $chat->customer_id,
            'payment_method_id' => $paymentMethodId,
            'payment_status' => Order::PAYMENT_STATUS_UNPAID,
            'is_chat_quote' => true,
            'billing_address' => $billingAddress ? $billingAddress->id : ($totals['billing_address'] ?? ''),
            'shipping' => (float) ($totals['shipping_cost'] ?? 0),
            'taxes' => (float) ($totals['tax'] ?? 0),
            'discount' => (float) ($totals['discount'] ?? 0),
            'packaging' => 0,
            'cart' => $cart,
            'delete_the_cart' => false,
        ] + $addressFields);

        $order = app(OrderRepository::class)->store($request);

        OrderChatSyncService::sendToShopChat(
            $order,
            trim((string) ($totals['note'] ?? '')),
            'merchant',
            true,
            $vendorUser->id,
            null,
            null,
            true
        );

        return $order;
    }
}
