<?php

namespace Incevio\Package\Wallet\Traits;

use App\Services\DbService;
use Incevio\Package\Wallet\Enums\TransferStatuses;
use Incevio\Package\Wallet\Interfaces\Mathable;
// use Incevio\Package\Wallet\Interfaces\Wallet;
use Incevio\Package\Wallet\Interfaces\Product;
use Incevio\Package\Wallet\Models\Transfer;
use Incevio\Package\Wallet\Objects\Bring;
use Incevio\Package\Wallet\Services\CommonService;
use Incevio\Package\Wallet\Services\LockService;
use Incevio\Package\Wallet\Services\WalletService;
use Throwable;

use function app;

/**
 * Trait HasGift.
 */
trait HasGift
{
    /**
     * Give the goods safely.
     *
     * @param  Wallet  $to
     */
    public function safeGift($to, Product $product, ?bool $force = null): ?Transfer
    {
        try {
            return $this->gift($to, $product, $force);
        } catch (Throwable $throwable) {
            return null;
        }
    }

    /**
     * From this moment on, each user (wallet) can give
     * the goods to another user (wallet).
     * This functionality can be organized for gifts.
     *
     * @param  Wallet  $to
     */
    public function gift($to, Product $product, ?bool $force = null): Transfer
    {
        return app(LockService::class)->lock($this, __FUNCTION__, function () use ($to, $product, $force) {
            /**
             * Who's giving? Let's call him Santa Claus.
             *
             * @var Wallet $santa
             */
            $santa = $this;

            /**
             * Unfortunately,
             * I think it is wrong to make the "assemble" method public.
             * That's why I address him like this!
             */
            return DbService::transaction(static function () use ($santa, $to, $product, $force) {
                $math = app(Mathable::class);
                $discount = app(WalletService::class)->discount($santa, $product);
                $amount = $math->sub($product->getAmountProduct($santa), $discount);
                $meta = $product->getMetaProduct();
                $fee = app(WalletService::class)->fee($product, $amount);

                $commonService = app(CommonService::class);

                /**
                 * Santa pays taxes.
                 */
                if (! $force) {
                    $commonService->verifyWithdraw($santa, $math->add($amount, $fee));
                }

                $withdraw = $commonService->forceWithdraw($santa, $math->add($amount, $fee), $meta);
                $deposit = $commonService->deposit($product, $amount, $meta);

                $from = app(WalletService::class)->getWallet($to);

                $transfers = $commonService->assemble([
                    app(Bring::class)
                        ->setStatus(TransferStatuses::STATUS_GIFT)
                        ->setDiscount($discount)
                        ->setDeposit($deposit)
                        ->setWithdraw($withdraw)
                        ->setFrom($from)
                        ->setTo($product),
                ]);

                return current($transfers);
            });
        });
    }

    /**
     * to give force).
     *
     * @param  Wallet  $to
     */
    public function forceGift($to, Product $product): Transfer
    {
        return $this->gift($to, $product, true);
    }
}
