<?php

namespace Incevio\Package\Wallet\Test\Models;

use Illuminate\Database\Eloquent\Model;
use Incevio\Package\Wallet\Traits\HasWallets;
use Incevio\Package\Wallet\Traits\MorphOneWallet;
use Laravel\Cashier\Billable;

/**
 * Class User.
 *
 * @property string $name
 * @property string $email
 */
class UserCashier extends Model
{
    use Billable, HasWallets, MorphOneWallet;

    public function getTable(): string
    {
        return 'users';
    }
}
