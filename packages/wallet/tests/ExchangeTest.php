<?php

namespace Incevio\Package\Wallet\Test;

use Incevio\Package\Wallet\Enums\TransferStatuses;
use Incevio\Package\Wallet\Test\Models\UserMulti;

class ExchangeTest extends TestCase
{
    public function test_simple(): void
    {
        /**
         * @var UserMulti $user
         */
        $user = factory(UserMulti::class)->create();
        $usd = $user->createWallet([
            'name' => 'My USD',
            'slug' => 'usd',
        ]);

        $rub = $user->createWallet([
            'name' => 'Мои рубли',
            'slug' => 'rub',
        ]);

        self::assertEquals(0, $rub->balance);
        self::assertEquals(0, $usd->balance);

        $rub->deposit(10000);

        self::assertEquals(10000, $rub->balance);
        self::assertEquals(0, $usd->balance);

        $transfer = $rub->exchange($usd, 10000);
        self::assertEquals(0, $rub->balance);
        self::assertEquals(147, $usd->balance);
        self::assertEquals(1.47, $usd->balanceFloat); // $1.47
        self::assertEquals(0, $transfer->fee);
        self::assertEquals(TransferStatuses::STATUS_EXCHANGE, $transfer->status);

        $transfer = $usd->exchange($rub, $usd->balance);
        self::assertEquals(0, $usd->balance);
        self::assertEquals(9938, $rub->balance);
        self::assertEquals(TransferStatuses::STATUS_EXCHANGE, $transfer->status);
    }

    public function test_safe(): void
    {
        /**
         * @var UserMulti $user
         */
        $user = factory(UserMulti::class)->create();
        $usd = $user->createWallet([
            'name' => 'My USD',
            'slug' => 'usd',
        ]);

        $rub = $user->createWallet([
            'name' => 'Мои рубли',
            'slug' => 'rub',
        ]);

        self::assertEquals(0, $rub->balance);
        self::assertEquals(0, $usd->balance);

        $transfer = $rub->safeExchange($usd, 10000);
        self::assertNull($transfer);
    }
}
