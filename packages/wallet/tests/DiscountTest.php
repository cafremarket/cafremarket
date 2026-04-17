<?php

namespace Incevio\Package\Wallet\Test;

use Incevio\Package\Wallet\Enums\TransferStatuses;
use Incevio\Package\Wallet\Exceptions\ProductEnded;
use Incevio\Package\Wallet\Models\Transaction;
use Incevio\Package\Wallet\Models\Wallet;
use Incevio\Package\Wallet\Test\Models\Buyer;
use Incevio\Package\Wallet\Test\Models\Item;
use Incevio\Package\Wallet\Test\Models\ItemDiscount;

class DiscountTest extends TestCase
{
    public function test_pay(): void
    {
        /**
         * @var Buyer $buyer
         * @var ItemDiscount $product
         */
        $buyer = factory(Buyer::class)->create();
        $product = factory(ItemDiscount::class)->create();

        self::assertEquals($buyer->balance, 0);
        $buyer->deposit($product->getAmountProduct($buyer));

        self::assertEquals($buyer->balance, $product->getAmountProduct($buyer));
        $transfer = $buyer->pay($product);
        self::assertNotNull($transfer);
        self::assertEquals($transfer->status, TransferStatuses::STATUS_PAID);

        self::assertEquals(
            $buyer->balance,
            $product->getPersonalDiscount($buyer)
        );

        self::assertEquals(
            $transfer->discount,
            $product->getPersonalDiscount($buyer)
        );

        /**
         * @var Transaction $withdraw
         * @var Transaction $deposit
         */
        $withdraw = $transfer->withdraw;
        $deposit = $transfer->deposit;

        self::assertInstanceOf(Transaction::class, $withdraw);
        self::assertInstanceOf(Transaction::class, $deposit);

        self::assertInstanceOf(Buyer::class, $withdraw->payable);
        self::assertInstanceOf(Item::class, $deposit->payable);

        self::assertEquals($buyer->getKey(), $withdraw->payable->getKey());
        self::assertEquals($product->getKey(), $deposit->payable->getKey());

        self::assertInstanceOf(Buyer::class, $transfer->from->holder);
        self::assertInstanceOf(Wallet::class, $transfer->from);
        self::assertInstanceOf(Item::class, $transfer->to);
        self::assertInstanceOf(Wallet::class, $transfer->to->wallet);

        self::assertEquals($buyer->wallet->getKey(), $transfer->from->getKey());
        self::assertEquals($buyer->getKey(), $transfer->from->holder->getKey());
        self::assertEquals($product->getKey(), $transfer->to->getKey());
    }

    public function test_item_transactions(): void
    {
        /**
         * @var Buyer $buyer
         * @var ItemDiscount $product
         */
        $buyer = factory(Buyer::class)->create();
        $product = factory(ItemDiscount::class)->create();

        self::assertEquals($buyer->balance, 0);
        $buyer->deposit($product->getAmountProduct($buyer));

        self::assertEquals($buyer->balance, $product->getAmountProduct($buyer));
        $transfer = $buyer->pay($product);
        self::assertNotNull($transfer);
        self::assertEquals($transfer->status, TransferStatuses::STATUS_PAID);

        self::assertEquals(
            $buyer->balance,
            $product->getPersonalDiscount($buyer)
        );

        self::assertEquals(
            $transfer->discount,
            $product->getPersonalDiscount($buyer)
        );

        /**
         * @var Transaction $withdraw
         * @var Transaction $deposit
         */
        $withdraw = $transfer->withdraw;
        $deposit = $transfer->deposit;

        self::assertInstanceOf(Transaction::class, $withdraw);
        self::assertInstanceOf(Transaction::class, $deposit);

        self::assertTrue($withdraw->is(
            $buyer->transactions()
                ->where('type', Transaction::TYPE_WITHDRAW)
                ->latest()
                ->first()
        ));

        self::assertTrue($deposit->is($product->transactions()->latest()->first()));
    }

    public function test_refund(): void
    {
        /**
         * @var Buyer $buyer
         * @var ItemDiscount $product
         */
        $buyer = factory(Buyer::class)->create();
        $product = factory(ItemDiscount::class)->create([
            'quantity' => 1,
        ]);

        self::assertEquals($buyer->balance, 0);
        $buyer->deposit($product->getAmountProduct($buyer));

        self::assertEquals($buyer->balance, $product->getAmountProduct($buyer));
        $transfer = $buyer->pay($product);
        self::assertNotNull($transfer);
        self::assertEquals($transfer->status, TransferStatuses::STATUS_PAID);

        self::assertEquals(
            $transfer->discount,
            $product->getPersonalDiscount($buyer)
        );

        self::assertTrue($buyer->refund($product));
        self::assertEquals($buyer->balance, $product->getAmountProduct($buyer));
        self::assertEquals($product->balance, 0);

        $transfer->refresh();
        self::assertEquals($transfer->status, TransferStatuses::STATUS_REFUND);

        self::assertFalse($buyer->safeRefund($product));
        self::assertEquals($buyer->balance, $product->getAmountProduct($buyer));

        $transfer = $buyer->pay($product);
        self::assertNotNull($transfer);
        self::assertEquals($buyer->balance, $product->getPersonalDiscount($buyer));
        self::assertEquals(
            $product->balance,
            $product->getAmountProduct($buyer) - $product->getPersonalDiscount($buyer)
        );

        self::assertEquals($transfer->status, TransferStatuses::STATUS_PAID);

        self::assertTrue($buyer->refund($product));
        self::assertEquals($buyer->balance, $product->getAmountProduct($buyer));
        self::assertEquals($product->balance, 0);

        $transfer->refresh();
        self::assertEquals($transfer->status, TransferStatuses::STATUS_REFUND);
    }

    public function test_force_refund(): void
    {
        /**
         * @var Buyer $buyer
         * @var ItemDiscount $product
         */
        $buyer = factory(Buyer::class)->create();
        $product = factory(ItemDiscount::class)->create([
            'quantity' => 1,
        ]);

        self::assertEquals($buyer->balance, 0);
        $buyer->deposit($product->getAmountProduct($buyer));

        self::assertEquals($buyer->balance, $product->getAmountProduct($buyer));

        $transfer = $buyer->pay($product);
        self::assertEquals($buyer->balance, $product->getPersonalDiscount($buyer));

        self::assertEquals(
            $product->balance,
            $product->getAmountProduct($buyer) - $product->getPersonalDiscount($buyer)
        );

        self::assertEquals(
            $transfer->discount,
            $product->getPersonalDiscount($buyer)
        );

        $product->withdraw($product->balance);
        self::assertEquals($product->balance, 0);

        self::assertFalse($buyer->safeRefund($product));
        self::assertTrue($buyer->forceRefund($product));

        self::assertEquals(
            $product->balance,
            -($product->getAmountProduct($buyer) - $product->getPersonalDiscount($buyer))
        );

        self::assertEquals($buyer->balance, $product->getAmountProduct($buyer));
        $product->deposit(-$product->balance);
        $buyer->withdraw($buyer->balance);

        self::assertEquals($product->balance, 0);
        self::assertEquals($buyer->balance, 0);
    }

    public function test_out_of_stock(): void
    {
        $this->expectException(ProductEnded::class);
        $this->expectExceptionMessageStrict(trans('packages.wallet.product_stock'));

        /**
         * @var Buyer $buyer
         * @var ItemDiscount $product
         */
        $buyer = factory(Buyer::class)->create();
        $product = factory(ItemDiscount::class)->create([
            'quantity' => 1,
        ]);

        $buyer->deposit($product->getAmountProduct($buyer));
        $buyer->pay($product);
        $buyer->pay($product);
    }

    public function test_force_pay(): void
    {
        /**
         * @var Buyer $buyer
         * @var ItemDiscount $product
         */
        $buyer = factory(Buyer::class)->create();
        $product = factory(ItemDiscount::class)->create([
            'quantity' => 1,
        ]);

        self::assertEquals($buyer->balance, 0);
        $buyer->forcePay($product);

        self::assertEquals(
            $buyer->balance,
            -($product->getAmountProduct($buyer) - $product->getPersonalDiscount($buyer))
        );

        $buyer->deposit(-$buyer->balance);
        self::assertEquals($buyer->balance, 0);
    }

    public function test_pay_free(): void
    {
        /**
         * @var Buyer $buyer
         * @var ItemDiscount $product
         */
        $buyer = factory(Buyer::class)->create();
        $product = factory(ItemDiscount::class)->create([
            'quantity' => 1,
        ]);

        self::assertEquals($buyer->balance, 0);

        $transfer = $buyer->payFree($product);
        self::assertEquals($transfer->deposit->type, Transaction::TYPE_DEPOSIT);
        self::assertEquals($transfer->withdraw->type, Transaction::TYPE_WITHDRAW);

        self::assertEquals($buyer->balance, 0);
        self::assertEquals($product->balance, 0);

        $buyer->refund($product);
        self::assertEquals($buyer->balance, 0);
        self::assertEquals($product->balance, 0);
    }

    public function test_free_pay(): void
    {
        /**
         * @var Buyer $buyer
         * @var ItemDiscount $product
         */
        $buyer = factory(Buyer::class)->create();
        $product = factory(ItemDiscount::class)->create([
            'quantity' => 1,
        ]);

        $buyer->forceWithdraw(1000);
        self::assertEquals($buyer->balance, -1000);

        $transfer = $buyer->payFree($product);
        self::assertEquals($transfer->deposit->type, Transaction::TYPE_DEPOSIT);
        self::assertEquals($transfer->withdraw->type, Transaction::TYPE_WITHDRAW);

        self::assertEquals($buyer->balance, -1000);
        self::assertEquals($product->balance, 0);

        $buyer->refund($product);
        self::assertEquals($buyer->balance, -1000);
        self::assertEquals($product->balance, 0);
    }

    public function test_pay_free_out_of_stock(): void
    {
        $this->expectException(ProductEnded::class);
        $this->expectExceptionMessageStrict(trans('packages.wallet.product_stock'));

        /**
         * @var Buyer $buyer
         * @var ItemDiscount $product
         */
        $buyer = factory(Buyer::class)->create();
        $product = factory(ItemDiscount::class)->create([
            'quantity' => 1,
        ]);

        self::assertNotNull($buyer->payFree($product));
        $buyer->payFree($product);
    }
}
