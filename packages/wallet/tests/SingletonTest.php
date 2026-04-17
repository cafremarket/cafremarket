<?php

namespace Incevio\Package\Wallet\Test;

use App\Services\DbService;
use Incevio\Package\Wallet\Interfaces\Mathable;
use Incevio\Package\Wallet\Interfaces\Rateable;
use Incevio\Package\Wallet\Interfaces\Storable;
use Incevio\Package\Wallet\Objects\Bring;
use Incevio\Package\Wallet\Objects\Cart;
use Incevio\Package\Wallet\Objects\EmptyLock;
use Incevio\Package\Wallet\Objects\Operation;
use Incevio\Package\Wallet\Services\CommonService;
use Incevio\Package\Wallet\Services\ExchangeService;
use Incevio\Package\Wallet\Services\LockService;
use Incevio\Package\Wallet\Services\WalletService;
use Incevio\Package\Wallet\Test\Common\Models\Transaction;
use Incevio\Package\Wallet\Test\Common\Models\Transfer;
use Incevio\Package\Wallet\Test\Common\Models\Wallet;

class SingletonTest extends TestCase
{
    protected function getRefId(string $object): string
    {
        return spl_object_hash(app($object));
    }

    public function test_bring(): void
    {
        self::assertNotEquals($this->getRefId(Bring::class), $this->getRefId(Bring::class));
    }

    public function test_cart(): void
    {
        self::assertNotEquals($this->getRefId(Cart::class), $this->getRefId(Cart::class));
    }

    public function test_empty_lock(): void
    {
        self::assertNotEquals($this->getRefId(EmptyLock::class), $this->getRefId(EmptyLock::class));
    }

    public function test_operation(): void
    {
        self::assertNotEquals($this->getRefId(Operation::class), $this->getRefId(Operation::class));
    }

    public function test_rateable(): void
    {
        self::assertEquals($this->getRefId(Rateable::class), $this->getRefId(Rateable::class));
    }

    public function test_storable(): void
    {
        self::assertEquals($this->getRefId(Storable::class), $this->getRefId(Storable::class));
    }

    public function test_mathable(): void
    {
        self::assertEquals($this->getRefId(Mathable::class), $this->getRefId(Mathable::class));
    }

    public function test_transaction(): void
    {
        self::assertNotEquals($this->getRefId(Transaction::class), $this->getRefId(Transaction::class));
    }

    public function test_transfer(): void
    {
        self::assertNotEquals($this->getRefId(Transfer::class), $this->getRefId(Transfer::class));
    }

    public function test_wallet(): void
    {
        self::assertNotEquals($this->getRefId(Wallet::class), $this->getRefId(Wallet::class));
    }

    public function test_exchange_service(): void
    {
        self::assertEquals($this->getRefId(ExchangeService::class), $this->getRefId(ExchangeService::class));
    }

    public function test_common_service(): void
    {
        self::assertEquals($this->getRefId(CommonService::class), $this->getRefId(CommonService::class));
    }

    public function test_wallet_service(): void
    {
        self::assertEquals($this->getRefId(WalletService::class), $this->getRefId(WalletService::class));
    }

    public function test_db_service(): void
    {
        self::assertEquals($this->getRefId(DbService::class), $this->getRefId(DbService::class));
    }

    public function test_lock_service(): void
    {
        self::assertEquals($this->getRefId(LockService::class), $this->getRefId(LockService::class));
    }
}
