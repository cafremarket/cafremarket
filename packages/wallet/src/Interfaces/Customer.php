<?php

namespace Incevio\Package\Wallet\Interfaces;

use Incevio\Package\Wallet\Models\Transfer;
use Incevio\Package\Wallet\Objects\Cart;

interface Customer extends Wallet
{
    /**
     * @throws
     */
    public function pay(Product $product, ?bool $force = null): Transfer;

    /**
     * @throws
     */
    public function safePay(Product $product, ?bool $force = null): ?Transfer;

    /**
     * @throws
     */
    public function forcePay(Product $product): Transfer;

    public function paid(Product $product, ?bool $gifts = null): ?Transfer;

    /**
     * @throws
     */
    public function refund(Product $product, ?bool $force = null, ?bool $gifts = null): bool;

    public function safeRefund(Product $product, ?bool $force = null, ?bool $gifts = null): bool;

    public function forceRefund(Product $product, ?bool $gifts = null): bool;

    /**
     * @return Transfer[]
     *
     * @throws
     */
    public function payCart(Cart $cart, ?bool $force = null): array;

    /**
     * @return Transfer[]
     *
     * @throws
     */
    public function safePayCart(Cart $cart, ?bool $force = null): array;

    /**
     * @return Transfer[]
     *
     * @throws
     */
    public function forcePayCart(Cart $cart): array;

    /**
     * @throws
     */
    public function refundCart(Cart $cart, ?bool $force = null, ?bool $gifts = null): bool;

    public function safeRefundCart(Cart $cart, ?bool $force = null, ?bool $gifts = null): bool;

    public function forceRefundCart(Cart $cart, ?bool $gifts = null): bool;
}
