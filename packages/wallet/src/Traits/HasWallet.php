<?php

namespace Incevio\Package\Wallet\Traits;

use App\Services\DbService;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
// use Incevio\Package\Wallet\Interfaces\Wallet;
use Illuminate\Support\Collection;
use Incevio\Package\Wallet\Interfaces\Mathable;
use Incevio\Package\Wallet\Interfaces\Storable;
use Incevio\Package\Wallet\Models\Transaction;
use Incevio\Package\Wallet\Models\Transfer;
use Incevio\Package\Wallet\Models\Wallet as WalletModel;
use Incevio\Package\Wallet\Services\CommonService;
use Incevio\Package\Wallet\Services\WalletService;
use Throwable;

use function app;

/**
 * Trait HasWallet.
 *
 *
 * @property-read Collection|WalletModel[] $wallets
 * @property-read int $balance
 */
trait HasWallet
{
    use MorphOneWallet;

    /**
     * The input means in the system.
     *
     * @param  int  $amount
     *
     * @throws
     */
    public function deposit($amount, ?array $meta = null, bool $confirmed = true): Transaction
    {
        $self = $this;

        return DbService::transaction(static function () use ($self, $amount, $meta, $confirmed) {
            return app(CommonService::class)->deposit($self, $amount, $meta, $confirmed);
        });
    }

    /**
     * Magic laravel framework method, makes it
     *  possible to call property balance.
     *
     * Example:
     *  $user1 = User::first()->load('wallet');
     *  $user2 = User::first()->load('wallet');
     *
     * Without static:
     *  var_dump($user1->balance, $user2->balance); // 100 100
     *  $user1->deposit(100);
     *  $user2->deposit(100);
     *  var_dump($user1->balance, $user2->balance); // 200 200
     *
     * With static:
     *  var_dump($user1->balance, $user2->balance); // 100 100
     *  $user1->deposit(100);
     *  var_dump($user1->balance); // 200
     *  $user2->deposit(100);
     *  var_dump($user2->balance); // 300
     *
     * @return int|float
     *
     * @throws
     */
    public function getBalanceAttribute()
    {
        return app(Storable::class)->getBalance($this);
    }

    /**
     * all user actions on wallets will be in this method.
     */
    public function transactions(): MorphMany
    {
        return ($this instanceof WalletModel ? $this->holder : $this)
            ->morphMany(Transaction::class, 'payable')
            ->orderBy('created_at', 'desc');
    }

    /**
     * Return last deposited transaction.
     *
     * @return Transaction
     */
    public function lastDeposit(): MorphOne
    {
        return ($this instanceof WalletModel ? $this->holder : $this)
            ->morphOne(Transaction::class, 'payable')
            ->where('type', Transaction::TYPE_DEPOSIT)->latest();
    }

    /**
     * Return last deposited transection.
     *
     * @return Transaction
     */
    public function lastDebited(): MorphOne
    {
        return ($this instanceof WalletModel ? $this->holder : $this)
            ->morphOne(Transaction::class, 'payable')
            ->where('type', Transaction::TYPE_WITHDRAW)->latest();
    }

    /**
     * This method ignores errors that occur when transferring funds.
     *
     * @param  Wallet  $wallet
     * @param  int  $amount
     */
    public function safeTransfer($wallet, $amount, ?array $meta = null): ?Transfer
    {
        try {
            return $this->transfer($wallet, $amount, $meta);
        } catch (Throwable $throwable) {
            return null;
        }
    }

    /**
     * A method that transfers funds from host to host.
     *
     * @param  Wallet  $wallet
     * @param  int  $amount
     *
     * @throws
     */
    public function transfer($wallet, $amount, ?array $meta = null): Transfer
    {
        app(CommonService::class)->verifyWithdraw($this, $amount);

        return $this->forceTransfer($wallet, $amount, $meta);
    }

    /**
     * Withdrawals from the system.
     *
     * @param  int  $amount
     */
    public function withdraw($amount, ?array $meta = null, bool $confirmed = true, bool $approved = true): Transaction
    {
        app(CommonService::class)->verifyWithdraw($this, $amount);

        return $this->forceWithdraw($amount, $meta, $confirmed, $approved);
    }

    /**
     * Checks if you can withdraw funds.
     *
     * @param  int  $amount
     */
    public function canWithdraw($amount, ?bool $allowZero = null): bool
    {
        $math = app(Mathable::class);

        /**
         * Allow to buy for free with a negative balance.
         */
        if ($allowZero && ! $math->compare($amount, 0)) {
            return true;
        }

        return $math->compare($this->balance, $amount) >= 0;
    }

    /**
     * Forced to withdraw funds from system.
     *
     * @param  int  $amount
     *
     * @throws
     */
    public function forceWithdraw($amount, ?array $meta = null, bool $confirmed = true, bool $approved = true): Transaction
    {
        $self = $this;

        return DbService::transaction(static function () use ($self, $amount, $meta, $confirmed, $approved) {
            return app(CommonService::class)->forceWithdraw($self, $amount, $meta, $confirmed, $approved);
        });
    }

    /**
     * the forced transfer is needed when the user does not have the money and we drive it.
     * Sometimes you do. Depends on business logic.
     *
     * @param  Wallet  $wallet
     * @param  int  $amount
     *
     * @throws
     */
    public function forceTransfer($wallet, $amount, ?array $meta = null): Transfer
    {
        $self = $this;

        return DbService::transaction(static function () use ($self, $amount, $wallet, $meta) {
            return app(CommonService::class)->forceTransfer($self, $wallet, $amount, $meta);
        });
    }

    /**
     * the transfer table is used to confirm the payment
     * this method receives all transfers.
     */
    public function transfers(): MorphMany
    {
        return app(WalletService::class)->getWallet($this, false)
            ->morphMany(Transfer::class, 'from');
    }
}
