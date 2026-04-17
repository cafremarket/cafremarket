<?php

namespace Incevio\Package\Wallet\Test\Models;

use Illuminate\Database\Eloquent\Model;
use Incevio\Package\Wallet\Interfaces\Wallet;
use Incevio\Package\Wallet\Interfaces\WalletFloat;
use Incevio\Package\Wallet\Traits\HasWalletFloat;
use Incevio\Package\Wallet\Traits\HasWallets;

/**
 * Class User.
 *
 * @property string $name
 * @property string $email
 */
class UserMulti extends Model implements Wallet, WalletFloat
{
    use HasWalletFloat, HasWallets;

    public function getTable(): string
    {
        return 'users';
    }
}
