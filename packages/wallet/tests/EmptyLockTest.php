<?php

namespace Incevio\Package\Wallet\Test;

use Incevio\Package\Wallet\Objects\EmptyLock;

class EmptyLockTest extends TestCase
{
    public function test_simple(): void
    {
        $empty = app(EmptyLock::class);
        self::assertTrue($empty->block(1));
        self::assertTrue($empty->block(1, null));
        self::assertNull($empty->get());
        self::assertTrue($empty->get(static function () {
            return true;
        }));
    }

    public function test_owner(): void
    {
        $empty = app(EmptyLock::class);
        $str = $empty->owner();
        self::assertIsString($str);
        self::assertEquals($str, $empty->owner());
    }
}
