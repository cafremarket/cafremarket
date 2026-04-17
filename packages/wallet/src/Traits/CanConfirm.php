<?php

namespace Incevio\Package\Wallet\Traits;

use App\Services\DbService;
use Incevio\Package\Wallet\Exceptions\ConfirmedInvalid;
use Incevio\Package\Wallet\Exceptions\WalletOwnerInvalid;
use Incevio\Package\Wallet\Interfaces\Mathable;
use Incevio\Package\Wallet\Models\Transaction;
use Incevio\Package\Wallet\Services\CommonService;
use Incevio\Package\Wallet\Services\LockService;
use Incevio\Package\Wallet\Services\WalletService;

trait CanConfirm
{
    public function confirm(Transaction $transaction): bool
    {
        return app(LockService::class)->lock($this, __FUNCTION__, function () use ($transaction) {
            $self = $this;

            return DbService::transaction(static function () use ($self, $transaction) {
                // $wallet = $transaction->wallet;
                // $wallet = app(WalletService::class)->getWallet($self);
                if (! $self->refreshBalance()) {
                    return false;
                }

                // echo "<pre>"; print_r($self->toArray()); echo "<pre>"; exit('end!');

                if ($transaction->type === Transaction::TYPE_WITHDRAW) {
                    app(CommonService::class)->verifyWithdraw(
                        $self,
                        app(Mathable::class)->abs($transaction->amount)
                    );
                }

                return $self->forceConfirm($transaction);
            });
        });
    }

    public function safeConfirm(Transaction $transaction): bool
    {
        try {
            return $this->confirm($transaction);
        } catch (\Throwable $throwable) {
            return false;
        }
    }

    /**
     * Removal of confirmation (forced), use at your own peril and risk.
     */
    public function resetConfirm(Transaction $transaction): bool
    {
        return app(LockService::class)->lock($this, __FUNCTION__, function () use ($transaction) {
            $self = $this;

            return DbService::transaction(static function () use ($self, $transaction) {
                // $wallet = app(WalletService::class)->getWallet($self);
                if (! $self->refreshBalance()) {
                    return false;
                }

                if (! $transaction->confirmed) {
                    throw new ConfirmedInvalid(trans('packages.wallet.unconfirmed_invalid'));
                }

                return $transaction->update(['confirmed' => false]) &&

                    // update balance
                    app(CommonService::class)->addBalance($self, -$transaction->amount);
            });
        });
    }

    public function safeResetConfirm(Transaction $transaction): bool
    {
        try {
            return $this->resetConfirm($transaction);
        } catch (\Throwable $throwable) {
            return false;
        }
    }

    /**
     * @throws ConfirmedInvalid
     * @throws WalletOwnerInvalid
     */
    public function forceConfirm(Transaction $transaction): bool
    {
        return app(LockService::class)->lock($this, __FUNCTION__, function () use ($transaction) {
            $self = $this;

            return DbService::transaction(static function () use ($self, $transaction) {
                // $wallet = $transaction->wallet;
                // $wallet = app(WalletService::class)->getWallet($self);

                if ($transaction->confirmed) {
                    throw new ConfirmedInvalid(trans('packages.wallet.confirmed_invalid'));
                }

                if ($self->getKey() !== $transaction->wallet_id) {
                    throw new WalletOwnerInvalid(trans('packages.wallet.owner_invalid'));
                }

                return $transaction->update(['confirmed' => true]) &&

                    // update balance
                    app(CommonService::class)->addBalance($self, $transaction->amount);
            });
        });
    }
}
